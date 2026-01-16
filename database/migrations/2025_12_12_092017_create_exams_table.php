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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            
            // Relacionamentos
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('main_subject_id')->nullable()->constrained('subjects')->onDelete('set null');
            
            // Informações básicas
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('exam_date')->nullable();
            
            // Configurações de layout (JSON)
            $table->json('header_config')->nullable()->comment('Configurações do cabeçalho da prova');
            $table->json('format_config')->nullable()->comment('Configurações de formatação (fonte, margens, colunas, etc)');
            $table->json('footer_config')->nullable()->comment('Configurações do rodapé da prova');
            
            // Distribuições de questões (JSON)
            $table->json('difficulty_distribution')->nullable()->comment('Distribuição por dificuldade {easy, medium, hard}');
            $table->json('topic_distribution')->nullable()->comment('Distribuição por tópicos [{topic_id, question_count}]');
            
            // Targets e totais
            $table->decimal('target_total_points', 10, 2)->nullable()->comment('Pontuação total desejada');
            $table->integer('target_question_count')->nullable()->comment('Quantidade de questões desejada');
            $table->decimal('total_points', 10, 2)->default(0.00)->comment('Pontuação total calculada');
            
            // Controle
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            
            // Timestamps e soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para performance
            $table->index('user_id');
            $table->index('main_subject_id');
            $table->index('exam_date');
            $table->index('is_published');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};