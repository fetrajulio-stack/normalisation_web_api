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
        Schema::create('historique_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulaire_id')->constrained('formulaires')->onDelete('cascade');
            $table->integer('nombre_enregistrements');
            $table->json('regles_appliquees')->nullable();
            $table->unsignedBigInteger('utilisateur_id')->nullable();
            $table->string('format_export', 50)->nullable();
            $table->string('fichier_genere')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('formulaire_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('historique_exports');
    }
};
