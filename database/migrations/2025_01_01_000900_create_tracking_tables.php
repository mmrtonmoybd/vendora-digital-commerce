<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attribution: which platform sent this customer, and which ad click did it.
 *
 * Both the first touch and the last touch are kept. Marketing usually wants last-touch for
 * ROAS, but first-touch is what answers "where did this customer come from originally",
 * and once the data is thrown away neither question can be re-asked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key', 40)->unique();
            // Values of ?utm_source that mean this platform ("fb", "meta", "facebook").
            $table->json('aliases')->nullable();
            // Referrer host fragments that mean this platform ("facebook", "fb").
            $table->json('hosts')->nullable();
            $table->string('icon_key', 40)->nullable();
            $table->string('color', 40)->default('bg-stone-900');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('order_source_id')->nullable()->after('user_agent')->constrained()->nullOnDelete();

            $table->string('utm_source')->nullable()->after('order_source_id');
            $table->string('utm_medium')->nullable()->after('utm_source');
            $table->string('utm_campaign')->nullable()->after('utm_medium');
            $table->string('utm_term')->nullable()->after('utm_campaign');
            $table->string('utm_content')->nullable()->after('utm_term');

            // Click identifiers. They are what the ad platforms match a conversion against,
            // so they are stored on the order and not only in the session.
            $table->string('fbclid')->nullable()->after('utm_content');
            $table->string('ttclid')->nullable()->after('fbclid');
            $table->string('gclid')->nullable()->after('ttclid');
            $table->string('fbp')->nullable()->after('gclid');
            $table->string('fbc')->nullable()->after('fbp');

            $table->text('referrer')->nullable()->after('fbc');
            $table->text('landing_url')->nullable()->after('referrer');
            $table->foreignId('first_touch_source_id')->nullable()->after('landing_url')->constrained('order_sources')->nullOnDelete();

            $table->index(['order_source_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('signup_source_id')->nullable()->after('referred_by_id')->constrained('order_sources')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signup_source_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_source_id');
            $table->dropConstrainedForeignId('first_touch_source_id');
            $table->dropColumn([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
                'fbclid', 'ttclid', 'gclid', 'fbp', 'fbc', 'referrer', 'landing_url',
            ]);
        });

        Schema::dropIfExists('order_sources');
    }
};
