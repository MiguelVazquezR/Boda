<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fecha en la que se publican las mesas («Encuentra tu mesa»).
     *
     * Las mesas se distribuyen después de revisar las confirmaciones de
     * asistencia. Hasta que llegue esta fecha, la sección muestra un aviso
     * («vuelve el …») en lugar del buscador. Si se deja vacía, no hay aviso:
     * el buscador aparece en cuanto exista al menos una mesa asignada.
     */
    public function up(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            $table->date('tables_reveal_date')->nullable()->after('rsvp_deadline');
        });

        // Fecha inicial (editable desde el panel de administración).
        DB::table('wedding_settings')->update(['tables_reveal_date' => '2026-10-25']);
    }

    public function down(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            $table->dropColumn('tables_reveal_date');
        });
    }
};
