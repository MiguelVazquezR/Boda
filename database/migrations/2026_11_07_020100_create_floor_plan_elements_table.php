<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elementos decorativos del salón (pista de baile, barra, escenario…).
     */
    public function up(): void
    {
        Schema::create('floor_plan_elements', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 50);
            $table->string('name');
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->unsignedSmallInteger('width')->default(180);
            $table->unsignedSmallInteger('height')->default(120);
            $table->unsignedSmallInteger('rotation')->default(0);
            $table->string('color', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floor_plan_elements');
    }
};
