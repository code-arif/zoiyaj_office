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
        Schema::create('check_in_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained();
            $table->foreignId('client_id')->constrained('users');
            $table->foreignId('professional_id')->constrained('users');
            $table->integer('redeem_tier_id')->nullable();

            $table->timestamp('client_checked_in_at')->nullable();
            $table->timestamp('professional_confirmed_at')->nullable();

            $table->enum('status', ['waiting', 'confirmed'])->default('waiting');

            $table->boolean('points_given_on_checkin_confirmed')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_in_bookings');
    }
};
