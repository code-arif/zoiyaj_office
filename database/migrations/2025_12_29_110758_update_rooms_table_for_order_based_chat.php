<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {

            // Drop foreign keys first
            $table->dropForeign(['first_user_id']);
            $table->dropForeign(['second_user_id']);

            //  Drop unique constraint
            $table->dropUnique('rooms_first_user_id_second_user_id_unique');


            // Re-add foreign keys
            $table->foreign('first_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('second_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');


        });
    }

    public function down(): void
    {

    }
};
