<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->unsignedTinyInteger('age')->nullable()->after('last_name');
            $table->string('gender')->nullable()->after('age'); // femenino | masculino
            $table->foreignId('guest_group_id')
                ->nullable()
                ->after('gender')
                ->constrained('guest_groups')
                ->nullOnDelete();
            $table->string('phone', 10)->nullable()->after('guest_group_id');
            $table->string('origin')->nullable()->after('phone'); // foraneo | local
            $table->string('state')->nullable()->after('origin');
            $table->string('city')->nullable()->after('state');
        });

        // Backfill: dividir full_name existente en first_name / last_name
        // (se toma la primera palabra como nombre y el resto como apellidos).
        DB::table('guests')
            ->whereNull('first_name')
            ->orderBy('id')
            ->select('id', 'full_name')
            ->chunkById(200, function ($guests) {
                foreach ($guests as $guest) {
                    $parts = preg_split('/\s+/', trim($guest->full_name), 2);
                    $first = $parts[0] ?? null;
                    $last = $parts[1] ?? null;

                    DB::table('guests')->where('id', $guest->id)->update([
                        'first_name' => $first,
                        'last_name' => $last,
                    ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guest_group_id');
            $table->dropColumn([
                'first_name',
                'last_name',
                'age',
                'gender',
                'phone',
                'origin',
                'state',
                'city',
            ]);
        });
    }
};
