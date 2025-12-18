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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('question_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('document_id')->nullable()->constrained()->onDelete('set null');
            $table->text('statement');
            $table->text('explanation')->nullable();
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
            $table->decimal('points', 3, 1)->default(1.0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
