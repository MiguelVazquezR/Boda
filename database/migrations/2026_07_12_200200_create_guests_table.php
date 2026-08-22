<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('search_slug')->index();
            $table->unsignedTinyInteger('allowed_passes')->default(1);
            $table->enum('rsvp_status', ['pending', 'confirmed', 'declined'])->default('pending');
            $table->unsignedTinyInteger('confirmed_passes')->nullable();
            $table->string('confirmed_by_name')->nullable();
            $table->text('rsvp_message')->nullable();
            $table->timestamp('rsvp_responded_at')->nullable();
            $table->string('table_group')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
