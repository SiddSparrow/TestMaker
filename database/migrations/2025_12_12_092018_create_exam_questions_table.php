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
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            
            // Relacionamentos
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            
            // Ordem da questão na prova
            $table->integer('order')->default(0)->comment('Ordem de exibição da questão');
            
            // Pontuação customizada (override)
            $table->decimal('points_override', 10, 2)->nullable()->comment('Pontuação customizada para esta questão nesta prova');
            
            // Timestamps
            $table->timestamps();
            
            // Índices
            $table->unique(['exam_id', 'question_id']);
            $table->index('exam_id');
            $table->index('question_id');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};