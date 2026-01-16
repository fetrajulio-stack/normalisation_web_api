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
        Schema::create('champs_formulaire', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulaire_id')->constrained('formulaires')->onDelete('cascade');
            $table->string('code_champ', 100);
            $table->string('libelle');
            $table->enum('type_champ', ['text', 'number', 'select', 'checkbox', 'radio', 'textarea']);
            $table->json('valeurs_possibles')->nullable();
            $table->boolean('obligatoire')->default(false);
            $table->integer('ordre_affichage')->default(0);
            $table->timestamps();
            $table->unique(['formulaire_id', 'code_champ']);
            $table->index('formulaire_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('champs_formulaire');
    }
};
