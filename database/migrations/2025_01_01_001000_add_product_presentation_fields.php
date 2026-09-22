<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The bits of the product page the template hard-codes: the feature bullets and the
 * rating line. Kept on the product so staff can edit them per item rather than every
 * product claiming the same "4.9 · 412 reviews".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('features')->nullable()->after('short');
            // Tenths, so 4.9 is 49 and no float ever touches it.
            $table->unsignedTinyInteger('rating')->default(49)->after('total_sold');
            $table->unsignedInteger('reviews_count')->default(0)->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['features', 'rating', 'reviews_count']);
        });
    }
};
