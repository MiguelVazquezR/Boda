<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuración del salón en el diseñador de mesas: título, medidas,
     * cuadrícula y secciones adicionales.
     */
    public function up(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            $table->json('venue_layout')->nullable()->after('gallery_auto_approve');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            $table->dropColumn('venue_layout');
        });
    }
};
