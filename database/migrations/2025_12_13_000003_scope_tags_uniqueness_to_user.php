<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tags.name/slug were globally unique even though every tag belongs to a
 * user_id and the model scopes queries per-user — so two different
 * professors could never both have a tag named "Gramática". Scope the
 * uniqueness to (user_id, name)/(user_id, slug) instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropUnique(['slug']);
            $table->unique(['user_id', 'name']);
            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'name']);
            $table->dropUnique(['user_id', 'slug']);
            $table->unique('name');
            $table->unique('slug');
        });
    }
};
