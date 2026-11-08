<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos del diseñador de salón: posición, forma y capacidad de cada mesa,
     * y el asiento que ocupa cada invitado dentro de su mesa.
     */
    public function up(): void
    {
        Schema::table('guest_tables', function (Blueprint $table) {
            $table->unsignedSmallInteger('number')->nullable()->after('name');
            $table->string('shape', 20)->default('round')->after('number');
            $table->unsignedSmallInteger('capacity')->default(8)->after('shape');
            // null = todavía no se ha colocado en el plano (el panel le asigna
            // una posición inicial hasta que se guarde el acomodo).
            $table->integer('x')->nullable()->after('capacity');
            $table->integer('y')->nullable()->after('x');
            $table->unsignedSmallInteger('width')->default(180)->after('y');
            $table->unsignedSmallInteger('height')->default(180)->after('width');
            $table->unsignedSmallInteger('rotation')->default(0)->after('height');
            $table->json('seat_distribution')->nullable()->after('rotation');
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->unsignedSmallInteger('seat_number')->nullable()->after('table_group');
        });
    }

    public function down(): void
    {
        Schema::table('guest_tables', function (Blueprint $table) {
            $table->dropColumn([
                'number', 'shape', 'capacity', 'x', 'y',
                'width', 'height', 'rotation', 'seat_distribution',
            ]);
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn('seat_number');
        });
    }
};
