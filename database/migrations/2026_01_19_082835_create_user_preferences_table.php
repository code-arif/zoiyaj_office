<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // users table er sathe relation
            $table->text('allergies')->nullable();
            $table->text('ingredients_to_avoid')->nullable();
            $table->text('ethical_preferences')->nullable();
            $table->string('skin_type')->nullable();
            $table->string('hair_type')->nullable();
            $table->string('hair_texture')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_preferences');
    }
};
