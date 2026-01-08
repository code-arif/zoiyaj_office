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
        Schema::table('users', function (Blueprint $table) {
            $table->string('professional_name')->nullable();
            $table->string('professional_phone')->nullable();
            $table->string('professional_email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();

            $table->integer('years_in_business')->default(0);
            $table->boolean('is_promo_participation')->default(false);
            $table->boolean('is_sell_retail_products')->default(false);
            $table->json('accessibilties')->nullable();

            $table->string('logo_path')->nullable();
            $table->string('certificate_path')->nullable();
            $table->boolean('is_premium')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
