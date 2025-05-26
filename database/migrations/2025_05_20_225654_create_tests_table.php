<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->json('title'); // Stores both English and Arabic titles
            $table->json('description')->nullable();
            $table->integer('duration'); // in minutes
            $table->string('difficulty'); // Beginner, Intermediate, Advanced
            $table->string('college'); // islamic, law, political
            $table->integer('grade'); // 1, 2, 3, 4
            $table->string('term'); // First, Second
            $table->string('plan_type'); // free, basic, pro
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->onDelete('cascade');
            $table->json('text'); // Question text in both languages
            $table->string('type'); // mcq or written
            $table->integer('points')->default(1);
            $table->timestamps();
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->json('text'); // Option text in both languages
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('tests');
    }
};