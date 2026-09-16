<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vincula cada invitado con su invitación digital (pareja o persona sola).
     * Al eliminar la invitación, los invitados quedan sin invitación asignada.
     */
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->foreignId('invitation_id')
                ->nullable()
                ->after('guest_group_id')
                ->constrained('invitations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invitation_id');
        });
    }
};
