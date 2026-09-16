<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * URL del sitio de Canva con la invitación animada (música y transiciones).
     * Es editable desde el panel; si está vacía se usa config/wedding.php.
     */
    public function up(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            $table->string('canva_url', 500)->nullable()->after('rsvp_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            $table->dropColumn('canva_url');
        });
    }
};
