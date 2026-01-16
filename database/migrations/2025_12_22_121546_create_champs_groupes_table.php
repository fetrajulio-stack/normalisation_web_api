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
        Schema::create('champs_groupes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('groupe_id')->constrained('groupes_champs')->onDelete('cascade');
            $table->foreignId('champ_id')->constrained('champs_formulaire')->onDelete('cascade');
            $table->integer('ordre_dans_groupe')->default(0);
            $table->timestamps();
            $table->unique(['groupe_id', 'champ_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('champs_groupes');
    }
};
