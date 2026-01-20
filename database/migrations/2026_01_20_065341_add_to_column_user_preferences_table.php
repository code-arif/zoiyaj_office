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
        Schema::table('user_preferences', function (Blueprint $table) {

            $table->foreignId('preference_id')->constrained('preferences')->onDelete('cascade'); // preferences table er sathe relation
            $table->enum('type', ['skintype', 'hairtype', 'hairtexture', 'allergies', 'ingredients', 'ethical'])->default('skintype');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            //
        });
    }
};
