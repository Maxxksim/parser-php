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
        $this->logger->log('info', 'PARSER MESSAGE: start preparing data for main parsing.');

        try {
            $response = $this->request->makeRequest('GET', self::MAIN_PAGE_QUESTIONS_URL);
        } catch (Throwable $e) {
            $this->logger->log('error', 'PARSER MESSAGE (PREPARING DATA) Error: ' . $e->getMessage());
            exit();
        }

        $pageWithLinksToQuestionsSortedByAlphabet = new Crawler($response->getBody()->getContents(), self::BASE_URL);
        $linksListToQuestions = $pageWithLinksToQuestionsSortedByAlphabet->filter('ul.dnrg li a')->links();

        foreach ($linksListToQuestions as $linkToSections) {
            $response = $this->request->makeRequest('GET', $linkToSections->getUri());

            $pageWithLinksToQuestionsPaginatedBySection = new Crawler($response->getBody()->getContents(), self::BASE_URL);
            $linksListToQuestionsPaginatedBySection = $pageWithLinksToQuestionsPaginatedBySection->filter('ul.dnrg li a')->links();

            $this->redisClient->connection()->lpush('queue', array_map(fn($linkToSection) => $linkToSection->getUri(), $linksListToQuestionsPaginatedBySection));
        }
        $this->logger->log('info', 'PARSER MESSAGE: end preparing data for main parsing.');
    }

    public function parse(string $linkToSection): string|false
    {
        $response = $this->request->makeRequest('GET', $linkToSection);

        $pageWithQuestionsSortedByAlphabet = new Crawler($response->getBody()->getContents(), self::BASE_URL);

        $linksToQuestionsSortedByAlphabet = $pageWithQuestionsSortedByAlphabet->filter('tbody tr td.Question a')->links();

        $questionsWithAnswers = [];
        foreach ($linksToQuestionsSortedByAlphabet as $linkToQuestionWithAnswers) {
            $question = $linkToQuestionWithAnswers->getNode()->nodeValue;

            $response = $this->request->makeRequest('GET', $linkToQuestionWithAnswers->getUri());

            if ($response->getStatusCode() === 404) {
                $response = $this->request->makeRequest('GET', $this->makeUrlToSearchQuestion($question));

                $pageWithAnswers = new Crawler($response->getBody()->getContents(), self::BASE_URL);

                if ($pageWithAnswers->filter('form div#SearchresultsCounter strong')->text() === self::NOT_FOUND_QUESTION) {
                    continue;
                }

                $answersWithLengthsList = $this->getAnswersWitLengths($pageWithAnswers, $question, true);

            } else {
                $pageWithAnswers = new Crawler($response->getBody()->getContents());

                $answersWithLengthsList = $this->getAnswersWitLengths($pageWithAnswers, $question);
            }

            $questionsWithAnswers[$linkToSection][$question] = $answersWithLengthsList;
        }

        return $this->convertToJson($questionsWithAnswers);
    }

    public function convertToJson(array $data): string|false
    {
        return json_encode($data);
    }

    private function makeUrlToSearchQuestion(string $question): string
    {
        return 'https://www.kreuzwort-raetsel.net/suche.php?s=' . rawurlencode($question) . '&field=0&go=suchen#suchbox';
    }

    private function getAnswersWitLengths(Crawler $page, string $question, bool $checkQuestion = false): array
    {
        $answersWithLengthsList = [];
        $page->filter('tbody tr')->each(function (Crawler $node) use ($question, &$answersWithLengthsList, &$checkQuestion) {
            $questionFromPage = $node->filter('td.Question');
            $answerFromPage = $node->filter('td.Answer');
            $lengthFromPage = $node->filter('td.Length');

            if (!$checkQuestion || $question === $questionFromPage->text()) {
                $answersWithLengthsList[] = ['answer' => $answerFromPage->text(), 'length' => (int)$lengthFromPage->text()];
            }
        });

        return $answersWithLengthsList;
    }

}
