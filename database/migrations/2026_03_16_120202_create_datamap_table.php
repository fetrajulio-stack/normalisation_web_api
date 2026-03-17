<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('datamap', function (Blueprint $table) {
            $table->id();
            $table->integer('position');
            $table->integer('longueur');
            $table->unsignedBigInteger('codification_id');
            $table->unsignedBigInteger('champ_id');



            // Clés étrangères
            $table->foreign('codification_id')
                ->references('id')
                ->on('codifications')
                ->onDelete('cascade');

            $table->foreign('champ_id')
                ->references('id')
                ->on('champs')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('datamap');
    }
};
