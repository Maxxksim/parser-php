<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

return new class extends Migration {

    public function up(): void
    {
        Capsule::schema()->create('answer_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions', 'id')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('answer_id')->constrained('answers', 'id')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};