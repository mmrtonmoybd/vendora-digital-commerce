<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnUpdate()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description')->nullable();
            $table->string('icon_key', 40)->default('play');
            $table->string('image')->nullable();
            $table->string('gradient', 80)->default('from-rose-500 to-red-700');
            $table->string('section_title')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_home')->default(true);
            $table->boolean('show_in_nav')->default(false);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['show_on_home', 'is_active']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short')->nullable();
            $table->longText('description')->nullable();
            $table->longText('delivery_info')->nullable();
            $table->string('image')->nullable();
            $table->json('gallery')->nullable();
            $table->string('icon_key', 40)->default('play');
            $table->string('gradient', 80)->default('from-rose-500 to-red-700');

            // Prices are paisa. regular_price is the struck-through "was" price.
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('regular_price')->nullable();

            $table->integer('stock')->default(0);
            $table->unsignedInteger('min_qty')->default(1);
            $table->unsignedInteger('max_qty')->default(10);

            $table->string('badge_text', 40)->nullable();
            $table->string('badge_style', 30)->default('brand');

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_delivery_email')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('total_sold')->default(0);
            $table->unsignedBigInteger('views')->default(0);

            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['category_id', 'is_active', 'sort_order']);
            $table->index(['is_active', 'is_featured']);
            $table->index('total_sold');
            $table->index('created_at');
        });

        // MySQL/MariaDB only. The storefront search falls back to LIKE when this is absent.
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('products', function (Blueprint $table) {
                $table->fullText(['name', 'short', 'description'], 'products_fulltext');
            });
        }

        Schema::create('product_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('key', 60);
            $table->string('label');
            $table->string('type', 20)->default('text');
            $table->json('options')->nullable();
            $table->string('placeholder')->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'key']);
        });

        Schema::create('product_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->integer('change');
            $table->string('reason', 20);
            $table->nullableMorphs('reference');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->integer('stock_after');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('group', 20)->default('general');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['group', 'is_active', 'sort_order']);
        });

        Schema::create('faq_product', function (Blueprint $table) {
            $table->foreignId('faq_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['faq_id', 'product_id']);
        });

        Schema::create('related_products', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('related_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['product_id', 'related_id']);
        });

        Schema::create('slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_redirects');
        Schema::dropIfExists('related_products');
        Schema::dropIfExists('faq_product');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('product_stock_logs');
        Schema::dropIfExists('product_fields');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
