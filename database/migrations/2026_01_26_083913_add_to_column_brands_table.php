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
        Schema::table('brands', function (Blueprint $table) {
            $table->string('thumb')->nullable()->after('name');
            $table->string('promo_code')->nullable()->after('thumb');
            $table->string('redirect_url')->nullable()->after('promo_code');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            //
        });
    }
};
