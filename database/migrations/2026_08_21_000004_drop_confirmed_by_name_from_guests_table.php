<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada invitado confirma por sí mismo; ya no se registra "quién confirma".
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn('confirmed_by_name');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('confirmed_by_name')->nullable()->after('rsvp_status');
        });
    }
};
