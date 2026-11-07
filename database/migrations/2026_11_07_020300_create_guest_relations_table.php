<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vínculos entre invitados (pareja, matrimonio, familia, acompañante).
     * Se usan para avisar cuando alguien queda en una mesa distinta a la de
     * sus acompañantes.
     */
    public function up(): void
    {
        Schema::create('guest_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_guest_id')->constrained('guests')->cascadeOnDelete();
            $table->string('type', 20)->default('family');
            $table->timestamps();

            $table->unique(['guest_id', 'related_guest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_relations');
    }
};
