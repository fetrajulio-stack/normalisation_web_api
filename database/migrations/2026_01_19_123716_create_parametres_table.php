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
        Schema::create('parametres', function (Blueprint $table) {
            $table->id();
            $table->string('passe');
            $table->string('matricule');
            $table->date('date_creation')->nullable();
            $table->date('date_modification')->nullable();
            $table->string('image')->nullable();
            $table->string('serveur')->nullable();
            $table->string('local')->nullable();
            $table->string('ext')->nullable();

            $table->boolean('multi')->default(false);
            $table->string('type')->nullable();
            $table->integer('xcopie')->default(0);
            $table->integer('echant')->default(0);
            $table->integer('nb_fichier')->default(0);

            $table->boolean('normalisation')->default(false);
            $table->boolean('rejet_cq')->default(false);
            $table->boolean('illisible')->default(false);

            $table->string('adr')->nullable();
            $table->date('date_final')->nullable();

            $table->foreignId('codification_id')
                ->unique()
                ->constrained('codifications')
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
        Schema::dropIfExists('parametres');
    }
};
