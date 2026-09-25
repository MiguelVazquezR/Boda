<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos de la cuenta bancaria para regalos (segunda opción de regalo,
     * debajo de la lista de Amazon). Editables (y borrables) desde el panel;
     * si la CLABE está vacía, el bloque no se muestra en el sitio.
     */
    public function up(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            $table->string('gift_bank_name')->nullable()->after('gift_registry_url');
            $table->string('gift_bank_clabe')->nullable()->after('gift_bank_name');
            $table->string('gift_bank_holder')->nullable()->after('gift_bank_clabe');
        });

        // Datos iniciales de la cuenta (quedan editables desde el panel).
        DB::table('wedding_settings')->update([
            'gift_bank_name' => 'BBVA',
            'gift_bank_clabe' => '012180015412256086',
            'gift_bank_holder' => 'Magda Elizabeth Mendoza Rosas',
        ]);
    }

    public function down(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            $table->dropColumn(['gift_bank_name', 'gift_bank_clabe', 'gift_bank_holder']);
        });
    }
};
