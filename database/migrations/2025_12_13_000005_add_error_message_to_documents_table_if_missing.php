<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * documents.error_message is part of the original
 * 2025_12_12_091934_create_documents_table migration, but at least one
 * environment ran that migration before the column was added to the file
 * (migrations are tracked by name, not content, so editing an
 * already-applied migration has no effect there). ProcessDocumentExtractionJob
 * writes to this column on both the success and failure paths, so its
 * absence breaks document processing entirely — every job fails with
 * "column error_message does not exist", including the failed() handler
 * that's supposed to record that very failure.
 *
 * Guarded with hasColumn() so this is a no-op on any database where the
 * column already exists (i.e. everywhere the original migration ran with
 * its current content).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('documents', 'error_message')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->text('error_message')->nullable()->after('extraction_result');
            });
        }
    }

    public function down(): void
    {
        // Not reversible: the column is part of the original table
        // definition elsewhere, so dropping it here would be wrong on
        // environments where it always existed.
    }
};
