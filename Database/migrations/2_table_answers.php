<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('answers', function (Blueprint $table) {
            $table->id();
            $table->string('answer')->unique();
            $table->integer('length');
            $table->timestamps();
        });
    }
};
