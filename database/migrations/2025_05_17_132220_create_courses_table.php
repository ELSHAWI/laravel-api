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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');                         // Course title
            $table->string('subtitle')->nullable();          // Optional subtitle
            $table->string('teacher')->nullable();           // Teacher name (nullable)
            $table->string('video_path')->nullable();        // Path to course video
            $table->string('summary_path')->nullable();      // Path to PDF summary
            $table->text('for_who')->nullable();             // Who the course is for
            $table->string('term')->nullable();              // Term (e.g., Fall 2025)
            $table->string('grade')->nullable();             // Grade (e.g., Freshman)
            $table->string('college')->nullable();           // College/department
            $table->text('description')->nullable();         // Full course description
            $table->string('image')->nullable();         // Full course description
            $table->string('plan_type');         // Full course description
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
