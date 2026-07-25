<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DocumentController::importQuestions() used to set status='imported',
 * a value outside the pending/processing/completed/failed enum, which
 * always threw after the questions had already been committed. Track the
 * import as a timestamp instead of inventing a new status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->timestamp('imported_at')->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('imported_at');
        });
    }
};
