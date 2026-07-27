<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * exams.is_published/published_at are part of the original
 * 2025_12_12_092017_create_exams_table migration, but at least one
 * environment ran that migration before the columns were added to the file
 * (migrations are tracked by name, not content, so editing an
 * already-applied migration has no effect there). ExamController's
 * store/update/togglePublish/duplicate all write to is_published, so its
 * absence breaks exam creation, publishing and duplication entirely with
 * "column is_published does not exist" — same drift already fixed once for
 * documents.error_message in 2025_12_13_000005.
 *
 * Guarded with hasColumn() so this is a no-op on any database where the
 * columns already exist (i.e. everywhere the original migration ran with
 * its current content).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'is_published')) {
                $table->boolean('is_published')->default(false)->after('total_points');
            }
            if (!Schema::hasColumn('exams', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('is_published');
            }
        });

        if (!$this->indexExists('exams', 'exams_is_published_index')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->index('is_published');
            });
        }
    }

    public function down(): void
    {
        // Not reversible: the columns are part of the original table
        // definition elsewhere, so dropping them here would be wrong on
        // environments where they always existed.
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(Schema::getIndexes($table))->pluck('name')->contains($indexName);
    }
};
