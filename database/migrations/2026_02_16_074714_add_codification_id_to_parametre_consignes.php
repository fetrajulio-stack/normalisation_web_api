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
        Schema::table('parametre_consignes', function (Blueprint $table) {
            $table->unsignedBigInteger('codification_id')->after('consigne_id');

            $table->foreign('codification_id')
                ->references('id')
                ->on('codifications')
                ->onDelete('cascade');

            $table->unique(['codification_id','consigne_id','cle']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parametre_consignes', function (Blueprint $table) {
            //
        });
    }
};
