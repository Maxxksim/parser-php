<?php

namespace App\Parser;

use App\Model\Answer;
use App\Model\Question;
use App\Request\Request;
use DiDom\Document;
use Symfony\Component\DomCrawler\Crawler;

class Parser
{
    private const string QUESTIONS_URL = 'https://www.kreuzwort-raetsel.net/uebersicht.html';
    private const string MAIN_URL = 'https://www.kreuzwort-raetsel.net/';

    private const string ANSWERS_URL = 'https://www.kreuzwort-raetsel.net/uebersicht-zeichen.html';

    public function __construct(private Request $request)
    {

    }

    public function parse(): void
    {

        $pageWithLinksToQuestionsSortedByAlphabet = $this->getConvertedPage(self::QUESTIONS_URL);
        $linksListToQuestions = $pageWithLinksToQuestionsSortedByAlphabet->filter('ul.dnrg li a')->links();

        foreach ($linksListToQuestions as $linkToSections) {

            $pageWithLinksToQuestionsPaginatedBySection = $this->getConvertedPage($linkToSections->getUri());
            $linksListToQuestionsPaginatedBySection = $pageWithLinksToQuestionsPaginatedBySection->filter('ul.dnrg li a')->links();

            foreach ($linksListToQuestionsPaginatedBySection as $linkToSection) {

                $pageWithQuestionsSortedByAlphabet = $this->getConvertedPage($linkToSection->getUri());

                $linksToQuestionsSortedByAlphabet = $pageWithQuestionsSortedByAlphabet->filter('tbody tr td.Question a')->links();

                foreach ($linksToQuestionsSortedByAlphabet as $linkToQuestionWithAnswers) {

                    $pageWithAnswers = $this->getConvertedPage($linkToQuestionWithAnswers->getUri());

                    $question = $linkToQuestionWithAnswers->getNode()->nodeValue;

                    $listWithAnswers = $pageWithAnswers->filter('tbody tr td.Answer a')->each(function (Crawler $node, $i): string {
                        return $node->text();
                    });
                    $listWithAnswersLengths = $pageWithAnswers->filter('tbody tr td.Length')->each(function (Crawler $node, $i): string {
                        return $node->text();
                    });

                    echo " ----- Question: $question -----" . PHP_EOL;
                    foreach ($listWithAnswers as $key => $answer) {
                        echo " -- Answer: $answer Length: $listWithAnswersLengths[$key]" . PHP_EOL;
                    }
                    echo PHP_EOL;
                }
            }

        }
    }

    private function getConvertedPage(string $url): Crawler
    {
        $page = $this->request->makeRequest('GET', $url);

        return new Crawler($page, self::MAIN_URL);
    }
}



