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
            Schema::create('consigne_groupe_champ', function (Blueprint $table) {
                $table->id();
                $table->foreignId('consigne_groupe_id')->constrained('consigne_groupes')->cascadeOnDelete();
                $table->foreignId('champ_id')->constrained('champs')->cascadeOnDelete();
                $table->integer('ordre')->default(1);
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
        Schema::dropIfExists('consigne_groupe_champ');
    }
};
