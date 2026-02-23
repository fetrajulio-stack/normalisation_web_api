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
            $table->unsignedBigInteger('consigne_id')->after('id');
            $table->foreign('consigne_id')
                ->references('id')
                ->on('consignes')
                ->onDelete('cascade');
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
            $table->dropForeign(['consigne_id']);
            $table->dropColumn('consigne_id');
        });
    }
};
