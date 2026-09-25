<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link de la mesa de regalos (lista de sugerencias en Amazon).
     * Es editable desde el panel; si está vacío se usa config/wedding.php.
     */
    public function up(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            $table->string('gift_registry_url', 500)->nullable()->after('canva_url');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            $table->dropColumn('gift_registry_url');
        });
    }
};
