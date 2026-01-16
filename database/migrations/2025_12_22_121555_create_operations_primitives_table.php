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
        Schema::create('operations_primitives', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->json('parametres_schema');
            $table->string('classe_php')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->index('code');
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
        Schema::dropIfExists('operations_primitives');
    }
};
