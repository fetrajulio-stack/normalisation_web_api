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
        Schema::create('logs_execution_regles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regle_id')->constrained('regles_export')->onDelete('cascade');
            $table->foreignId('enregistrement_id')->constrained('enregistrements')->onDelete('cascade');
            $table->boolean('succes')->default(true);
            $table->text('erreur')->nullable();
            $table->integer('duree_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('regle_id');
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
        Schema::dropIfExists('logs_execution_regles');
    }
};
