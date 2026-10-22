<?php

declare(strict_types=1);

namespace App\Parser;

use App\Logger\Logger;
use App\Redis\Redis;
use App\Request\Request;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;


class Parser
{
    private const string MAIN_PAGE_QUESTIONS_URL = 'https://www.kreuzwort-raetsel.net/uebersicht.html';

    private const string BASE_URL = 'https://www.kreuzwort-raetsel.net/';

    private const string NOT_FOUND_QUESTION = 'Die Suche hat leider keine Ergebnisse.';


    public function __construct(private Request $request, private Redis $redisClient, private Logger $logger)
    {
        $this->prepareDataToMainParsing();
    }

    private function prepareDataToMainParsing(): void
    {
        $this->logger->info('PARSER MESSAGE: start preparing data for main parsing.');

        try {
            $response = $this->request->makeRequest('GET', self::MAIN_PAGE_QUESTIONS_URL);
        } catch (Throwable $e) {
            $this->logger->error('PARSER MESSAGE (PREPARING DATA) Error: {error}', ['error' => $e->getMessage()]);

            exit(1);
        }

        $pageWithLinksToQuestionsSortedByAlphabet = new Crawler($response->getBody()->getContents(), self::BASE_URL);
        $linksListToQuestions = $pageWithLinksToQuestionsSortedByAlphabet->filter('ul.dnrg li a')->links();

        foreach ($linksListToQuestions as $linkToSections) {
            $response = $this->request->makeRequest('GET', $linkToSections->getUri());
            $linksListToQuestionsPaginatedBySection = new Crawler(
                $response->getBody()->getContents(),
                self::BASE_URL,
            )->filter('ul.dnrg li a')->links();

            array_map(fn($linkToSection) => $linkToSection->getUri(), $linksListToQuestionsPaginatedBySection)
                |> $this->takeOnlyNewLinks(...)
                |> (function (array $newLinks) {

                if ($newLinks !== []) {
                    $this->redisClient->connection()->transaction(function ($transaction) use ($newLinks) {
                        $transaction->lpush('queue', $newLinks);
                        $transaction->sadd('set:processing', $newLinks);
                    });
                }
            });

        }
        $this->logger->info('PARSER MESSAGE: end preparing data for main parsing.');
    }

    private function takeOnlyNewLinks(array $links): array
    {
        if ($links === []) {
            return [];
        }

        $onlyNewLinks = [];
        $resultProcessing = $this->redisClient->connection()->smismember('set:processing', ...$links);
        $resultParsed = $this->redisClient->connection()->smismember('set:parsed:links', ...$links);

        foreach ($links as $index => $link) {

            if ($resultParsed[$index] || $resultProcessing[$index]) {
                continue;
            } else {
                $onlyNewLinks[] = $link;
            }
        }
        return $onlyNewLinks;
    }

    public function parse(string $linkToSection): string
    {
        $questionsWithAnswers = [];
        $response = $this->request->makeRequest('GET', $linkToSection);
        $linksToQuestionsSortedByAlphabet = new Crawler(
            $response->getBody()->getContents(),
            self::BASE_URL,
        )->filter('tbody tr td.Question a')->links();

        foreach ($linksToQuestionsSortedByAlphabet as $linkToQuestionWithAnswers) {
            $question = $linkToQuestionWithAnswers->getNode()->nodeValue;
            $response = $this->request->makeRequest('GET', $linkToQuestionWithAnswers->getUri());

            if ($response->getStatusCode() === 404) {
                $pageWithAnswers = new Crawler($response->getBody()->getContents());

                if (!($result = $this->getAnswersWithLengths(
                    $pageWithAnswers,
                    $question,
                    true,
                ))) {
                    continue;
                }
                $answersWithLengthsList = $result;

            } else {
                $pageWithAnswers = new Crawler($response->getBody()->getContents());

                if (!($result = $this->getAnswersWithLengths(
                    $pageWithAnswers,
                    $question,
                ))) {
                    continue;
                }
                $answersWithLengthsList = $result;
            }
            $questionsWithAnswers[$linkToSection][$question] = $answersWithLengthsList;
        }
        return json_encode($questionsWithAnswers);
    }

    private function makeUrlToSearchQuestion(string $question): string
    {
        return 'https://www.kreuzwort-raetsel.net/suche.php?s=' . rawurlencode($question) . '&field=0&go=suchen#suchbox';
    }

    private function getAnswersWithLengths(Crawler $page, string $question, bool $checkQuestion = false): ?array
    {
        $answersWithLengthsList = [];

        if ($checkQuestion || !($page->filter('span[data-lf="more"][hidden]')->count() > 0)) {
            $page = new Crawler(
                $this->request->makeRequest('GET',
                    $this->makeUrlToSearchQuestion($question))->getBody()->getContents(),
                self::BASE_URL,
            );

            $div = $page->filter('form div#SearchresultsCounter strong');

            if ($div->count() === 0 || $div->text() === self::NOT_FOUND_QUESTION) {
                return null;
            }

            while (($nextLink = $page->filter('tfoot td ul.Pagination li.Next a'))->count() > 0) {
                $answersWithLengthsList = $this->parseAnswers(
                    $page,
                    $checkQuestion = true,
                    $question,
                    $answersWithLengthsList,
                );
                $page = new Crawler($this->request->makeRequest(
                    'GET', $nextLink->link()->getUri())->getBody()->getContents(),
                    self::BASE_URL,
                );
            }
        }
        return $this->parseAnswers($page, $checkQuestion, $question, $answersWithLengthsList);
    }

    private function parseAnswers(Crawler $page, bool $checkQuestion, string $question, array $answersWithLengthsList): ?array
    {

        foreach ($page->filter('tbody tr') as $tr) {
            $node = new Crawler($tr);

            $questionFromPage = $node->filter('td.Question');
            $answerFromPage = $node->filter('td.Answer');
            $lengthFromPage = $node->filter('td.Length');

            if ($answerFromPage->count() === 0) {
                continue;
            }

            if (!$checkQuestion || $question === $questionFromPage->text()) {
                $answersWithLengthsList[] = [
                    'answer' => $answerFromPage->text(),
                    'length' => (int)$lengthFromPage->text(),
                ];
            }
        }
        return $answersWithLengthsList;
    }

}
