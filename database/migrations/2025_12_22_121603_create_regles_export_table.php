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
        Schema::create('regles_export', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulaire_id')->constrained('formulaires')->onDelete('cascade');
            $table->string('nom_regle');
            $table->text('description')->nullable();
            $table->integer('priorite')->default(0);
            $table->json('conditions')->nullable();
            $table->json('pipeline');
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->index(['formulaire_id', 'priorite']);
            $table->index('actif');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('regles_export');
    }
};
