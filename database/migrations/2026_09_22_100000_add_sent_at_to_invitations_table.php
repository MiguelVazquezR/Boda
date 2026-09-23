<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fecha en la que la invitación se marcó como enviada a los invitados.
     *
     * `null` = todavía no se les manda el link. El panel de invitados usa este
     * campo para mostrar el check en verde de «enviada» (Parejas / Links) y la
     * etiqueta verde en la tabla de invitados.
     */
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn('sent_at');
        });
    }
};
