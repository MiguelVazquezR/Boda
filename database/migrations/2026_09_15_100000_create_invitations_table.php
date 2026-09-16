<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invitaciones digitales.
     *
     * Una invitación agrupa a 1 o 2 invitados (una pareja, o una persona sola si
     * no tiene pareja) y tiene un token público con el que se comparte el link
     * de la invitación (/i/{token}). Ese mismo token se usa para precargar la
     * confirmación de asistencia en la página principal.
     */
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('token', 16)->unique();
            // Cómo se muestra en la invitación: "Ana & Luis", "Familia López", etc.
            $table->string('display_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
