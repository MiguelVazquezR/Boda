<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuando está activo, las fotos que suben los invitados se publican
     * de inmediato en la galería, sin pasar por moderación.
     */
    public function up(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            $table->boolean('gallery_auto_approve')->default(false)->after('tables_reveal_date');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            $table->dropColumn('gallery_auto_approve');
        });
    }
};
