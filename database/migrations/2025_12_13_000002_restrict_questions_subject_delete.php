<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * questions.subject_id was declared ->onDelete('cascade'), so deleting a
 * Subject silently deleted every question that referenced it — contradicting
 * SubjectsModal.vue's own confirmation dialog, which promises questions are
 * only unlinked, never destroyed. subject_id is NOT NULL, so 'set null' is
 * not an option; 'restrict' is, forcing subjects with questions to be
 * cleared out (or reassigned) before they can be deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('subject_id')->references('id')->on('subjects')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
        });
    }
};
