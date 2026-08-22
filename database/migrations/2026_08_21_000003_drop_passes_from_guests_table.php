<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Elimina el concepto de "pases": cada invitado se registra individualmente.
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['allowed_passes', 'confirmed_passes']);
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->unsignedTinyInteger('allowed_passes')->default(1)->after('city');
            $table->unsignedTinyInteger('confirmed_passes')->nullable()->after('allowed_passes');
        });
    }
};
