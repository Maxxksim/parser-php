<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Question;
use Illuminate\Database\Capsule\Manager as Capsule;

class ParsedDataController
{
    public function save(string $data): string
    {
        $convertedData = json_decode($data, true);

        foreach ($convertedData as $questions) {

            foreach ($questions as $questionText => $answers) {

                $question = Question::firstOrCreate(['question' => $questionText]);

                Capsule::table('answers')->insertOrIgnore($answers);
                $answersList = array_column($answers, 'answer');
                $answersIdsList = Capsule::table('answers')->whereIn('answer', $answersList)->pluck('id');

                $question->answers()->syncWithoutDetaching($answersIdsList);
            }
        }

        return array_key_first($convertedData);
    }
}