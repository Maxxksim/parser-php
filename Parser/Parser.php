<?php

namespace App\Parser;

use App\Model\Answer;
use App\Model\Question;
use App\Redis\Redis;
use App\Request\Request;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Database\Capsule\Manager as Capsule;


class Parser
{
    private const string MAIN_PAGE_QUESTIONS_URL = 'https://www.kreuzwort-raetsel.net/uebersicht.html';
    private const string BASE_URL = 'https://www.kreuzwort-raetsel.net/';

    public function __construct(private Request $request, private Redis $redisClient)
    {
        $this->prepareDataToMainParsing();
    }

    private function prepareDataToMainParsing(): void
    {
        $pageWithLinksToQuestionsSortedByAlphabet = $this->getConvertedPage(self::MAIN_PAGE_QUESTIONS_URL);
        $linksListToQuestions = $pageWithLinksToQuestionsSortedByAlphabet->filter('ul.dnrg li a')->links();

        foreach ($linksListToQuestions as $linkToSections) {

            $pageWithLinksToQuestionsPaginatedBySection = $this->getConvertedPage($linkToSections->getUri());
            $linksListToQuestionsPaginatedBySection = $pageWithLinksToQuestionsPaginatedBySection->filter('ul.dnrg li a')->links();

            $this->redisClient->connection()->lpush('queue', array_map(fn($linkToSection) => $linkToSection->getUri(), $linksListToQuestionsPaginatedBySection));
        }
    }

    private function getConvertedPage(string $url): Crawler
    {
        $page = $this->request->makeRequest('GET', $url);

        return new Crawler($page, self::BASE_URL);
    }

    public function parse(string $linkToSection): void
    {
        $pageWithQuestionsSortedByAlphabet = $this->getConvertedPage($linkToSection);

        $linksToQuestionsSortedByAlphabet = $pageWithQuestionsSortedByAlphabet->filter('tbody tr td.Question a')->links();

        foreach ($linksToQuestionsSortedByAlphabet as $linkToQuestionWithAnswers) {
            $answersWithLengthList = [];

            $pageWithAnswers = $this->getConvertedPage($linkToQuestionWithAnswers->getUri());

            $question = $linkToQuestionWithAnswers->getNode()->nodeValue;

            $parsedListWithAnswers = $pageWithAnswers->filter('tbody tr td.Answer a')->each(function (Crawler $node): string {
                return $node->text();
            });
            $parsedListWithAnswersLengths = $pageWithAnswers->filter('tbody tr td.Length')->each(function (Crawler $node): string {
                return $node->text();
            });

            $question = Question::firstOrCreate(['question' => $question]);

            echo " ----- Question: $question -----" . PHP_EOL;
            foreach ($parsedListWithAnswers as $key => $answer) {

                $answersWithLengthList[] = ['answer' => $answer, 'length' => $parsedListWithAnswersLengths[$key]];

                echo " -- Answer: $answer Length: $parsedListWithAnswersLengths[$key]" . PHP_EOL;
            }
            echo PHP_EOL;

            Capsule::table('answers')->insertOrIgnore($answersWithLengthList);
            $answersList = array_column($answersWithLengthList, 'answer');
            $answersIdsList = Capsule::table('answers')->whereIn('answer', $answersList)->pluck('id');

            $question->answers()->syncWithoutDetaching($answersIdsList);

        }
    }
}





