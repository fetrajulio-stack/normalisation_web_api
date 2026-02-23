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

            Schema::create('consigne_groupes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('consigne_id')->constrained('consignes')->cascadeOnDelete();
                $table->string('libelle')->nullable();
                $table->integer('ordre_execution')->default(1);
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
        Schema::dropIfExists('consigne_groupes');
    }
};
