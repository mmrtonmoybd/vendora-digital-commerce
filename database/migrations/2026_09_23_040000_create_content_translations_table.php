<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Translations for text an administrator typed, as opposed to text a developer wrote.
 *
 * A side table rather than JSON columns on every model, for three reasons: it is purely
 * additive so no existing column changes type or meaning; one query loads every translation
 * the request could possibly need; and a language that is turned off simply stops being
 * read, leaving its rows intact for the day somebody turns it back on.
 *
 * `translatable_id` is a string because the same table serves models (keyed by id) and
 * settings groups (keyed by group name) — a settings row is stored with the type
 * "settings" and the id "homepage".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_translations', function (Blueprint $table): void {
            $table->id();
            $table->string('translatable_type', 120);
            $table->string('translatable_id', 120);
            $table->string('field', 80);
            $table->string('locale', 5);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(
                ['translatable_type', 'translatable_id', 'field', 'locale'],
                'content_translations_unique',
            );

            // The storefront reads every translation for one locale in a single query.
            $table->index(['locale', 'translatable_type'], 'content_translations_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_translations');
    }
};
