<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
    $table->text('description');
    $table->dateTime('start_datetime');
    $table->dateTime('end_datetime');
    $table->string('location');
    $table->string('image_path')->nullable();
    $table->string('category');
    $table->json('target_colleges')->nullable(); // ['law', 'islamic', 'political']
    $table->json('target_grades')->nullable(); // [1, 2, 3, 4]
    $table->json('target_terms')->nullable(); // ['First', 'Second']
    $table->json('target_plans')->nullable(); // ['free', 'basic', 'pro']
    $table->boolean('is_published')->default(false);
    $table->boolean('for_all_students')->default(false);
    $table->foreignId('created_by')->constrained('users');
            $table->enum('plan_type', ['free', 'basic', 'pro']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};