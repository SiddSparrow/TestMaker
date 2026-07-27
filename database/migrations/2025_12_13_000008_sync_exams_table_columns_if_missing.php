<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Same drift as 2025_12_13_000007 (is_published/published_at): the
 * environment ran 2025_12_12_092017_create_exams_table before several
 * columns were added to that file, and migrations are tracked by name, not
 * content, so those additions never reached that database. The first
 * symptom was is_published; the next was format_config. Rather than patch
 * one column per bug report, this checks every column the current
 * create_exams_table migration declares and adds whichever are still
 * missing, guarded with hasColumn() so it's a no-op wherever the schema is
 * already in sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'main_subject_id')) {
                $table->foreignId('main_subject_id')->nullable()->after('user_id')
                    ->constrained('subjects')->onDelete('set null');
            }
            if (!Schema::hasColumn('exams', 'header_config')) {
                $table->json('header_config')->nullable()->after('exam_date');
            }
            if (!Schema::hasColumn('exams', 'format_config')) {
                $table->json('format_config')->nullable()->after('header_config');
            }
            if (!Schema::hasColumn('exams', 'footer_config')) {
                $table->json('footer_config')->nullable()->after('format_config');
            }
            if (!Schema::hasColumn('exams', 'difficulty_distribution')) {
                $table->json('difficulty_distribution')->nullable()->after('footer_config');
            }
            if (!Schema::hasColumn('exams', 'topic_distribution')) {
                $table->json('topic_distribution')->nullable()->after('difficulty_distribution');
            }
            if (!Schema::hasColumn('exams', 'target_total_points')) {
                $table->decimal('target_total_points', 10, 2)->nullable()->after('topic_distribution');
            }
            if (!Schema::hasColumn('exams', 'target_question_count')) {
                $table->integer('target_question_count')->nullable()->after('target_total_points');
            }
            if (!Schema::hasColumn('exams', 'total_points')) {
                $table->decimal('total_points', 10, 2)->default(0.00)->after('target_question_count');
            }
            if (!Schema::hasColumn('exams', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        // Not reversible: these columns are part of the original table
        // definition elsewhere, so dropping them here would be wrong on
        // environments where they always existed.
    }
};
