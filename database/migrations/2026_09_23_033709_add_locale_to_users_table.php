<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers the language a person chose, so it survives a new device and a cleared session.
 *
 * Nullable on purpose: null means "never said", which is what lets browser detection keep
 * working for an account that has not expressed a preference.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('locale', 5)->nullable()->after('email');
        });

        Schema::table('admins', function (Blueprint $table): void {
            $table->string('locale', 5)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('locale'));
        Schema::table('admins', fn (Blueprint $table) => $table->dropColumn('locale'));
    }
};
