<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Access-grant delivery: nothing is downloaded, an admin invites the customer's email to a
 * Business Manager or ad account on the platform.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon_key', 40)->default('shield');
            $table->string('logo')->nullable();
            // What the customer must do once the invitation lands in their inbox.
            $table->longText('invite_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('platform_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->string('access_role')->nullable()->after('platform_id');
            $table->unsignedInteger('fulfillment_sla_minutes')->default(30)->after('access_role');
            $table->unsignedInteger('warranty_days')->nullable()->after('fulfillment_sla_minutes');
            $table->longText('customer_instructions')->nullable()->after('warranty_days');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('assigned_admin_id')->nullable()->after('processed_by')->constrained('admins')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('assigned_admin_id');
            $table->boolean('sla_alerted')->default(false)->after('assigned_at');

            $table->index(['assigned_admin_id', 'status']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('access_status', 30)->default('awaiting')->after('delivery_email');
            $table->timestamp('granted_at')->nullable()->after('access_status');
            $table->foreignId('granted_by')->nullable()->after('granted_at')->constrained('admins')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('granted_by');
            // Which Business Manager / account was actually shared. Staff-only: it names
            // an internal asset and must never reach the customer.
            $table->string('access_reference')->nullable()->after('confirmed_at');
            $table->longText('admin_delivery_note')->nullable()->after('access_reference');
            $table->timestamp('warranty_until')->nullable()->after('admin_delivery_note');

            $table->index(['access_status', 'granted_at']);
        });

        Schema::create('access_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->text('note')->nullable();
            $table->nullableMorphs('actor');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['order_item_id', 'created_at']);
        });

        Schema::create('access_problem_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_email')->nullable();
            $table->string('reason', 40);
            $table->text('message')->nullable();
            $table->string('screenshot')->nullable();
            $table->string('status', 20)->default('open');
            $table->text('resolution_note')->nullable();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_problem_reports');
        Schema::dropIfExists('access_events');

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('granted_by');
            $table->dropColumn([
                'access_status', 'granted_at', 'confirmed_at',
                'access_reference', 'admin_delivery_note', 'warranty_until',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_admin_id');
            $table->dropColumn(['assigned_at', 'sla_alerted']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('platform_id');
            $table->dropColumn(['access_role', 'fulfillment_sla_minutes', 'warranty_days', 'customer_instructions']);
        });

        Schema::dropIfExists('platforms');
    }
};
