<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Backfill: registra como mesa cada nombre ya usado por los invitados,
        // para poder gestionar (renombrar / eliminar) las mesas existentes.
        $now = now();

        $rows = DB::table('guests')
            ->whereNotNull('table_group')
            ->where('table_group', '!=', '')
            ->distinct()
            ->pluck('table_group')
            ->map(fn ($name) => [
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('guest_tables')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_tables');
    }
};
