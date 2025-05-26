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
        Schema::create('pdfs', function (Blueprint $table) {
            $table->id();
            $table->string('title');                        // PDF title
            $table->string('subtitle')->nullable();         // Optional subtitle
            $table->string('author')->nullable();           // Optional author/teacher
            $table->text('summary')->nullable();            // Optional description/summary
            $table->string('pdf_path');                     // File path of uploaded PDF
            $table->string('subject')->nullable();          // Subject (e.g., math, physics)
            $table->string('term')->nullable();             // Term (e.g., Spring 2025)
            $table->string('grade')->nullable();            // Grade (e.g., Sophomore)
            $table->string('college')->nullable();  
            $table->enum('plan_type', ['free', 'basic', 'pro']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdfs');
    }
};
