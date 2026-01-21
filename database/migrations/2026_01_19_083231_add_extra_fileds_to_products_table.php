<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
            $table->string('uniq_id')->unique()->nullable();
            $table->string('product_id')->nullable();

            $table->string('sku')->nullable();
            $table->text('upc')->nullable(); // ✅ BARCODE
            $table->string('asin')->nullable();

            $table->text('product_name');
            $table->string('brand_name')->nullable();
            $table->string('site_name')->nullable();

            $table->string('color')->nullable();

            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('original_price', 10, 2)->nullable();
            $table->string('currency', 10)->nullable();

            $table->string('country', 50)->nullable();
            $table->string('availability')->nullable();

            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->text('how_to_use')->nullable();
            $table->text('highlights')->nullable();
            $table->longText('ingredients')->nullable();

            $table->longText('raw_description')->nullable();
            $table->text('raw_how_to_use')->nullable();
            $table->longText('raw_ingredients')->nullable();

            $table->text('breadcrumbs')->nullable();
            $table->text('product_url')->nullable();
            $table->text('primary_image_url')->nullable();
            $table->longText('additional_images')->nullable();

            $table->timestamp('last_updated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
            $table->dropColumn([
                'uniq_id', 'product_id', 'sku', 'upc', 'asin', 'product_name', 'brand_name',
                'site_name', 'size', 'color', 'price', 'original_price', 'currency', 'country',
                'availability', 'summary', 'description', 'how_to_use', 'highlights', 'ingredients',
                'raw_description', 'raw_how_to_use', 'raw_ingredients', 'breadcrumbs',
                'product_url', 'primary_image_url', 'additional_images', 'last_updated'
            ]);
        });
    }
};
