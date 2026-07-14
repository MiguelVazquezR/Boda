<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_settings', function (Blueprint $table) {
            $table->id();
            $table->string('cover_photo_path')->nullable();
            $table->text('our_story')->nullable();
            $table->dateTime('event_datetime');
            $table->string('venue_name');
            $table->string('venue_address');
            $table->decimal('venue_lat', 10, 7)->nullable();
            $table->decimal('venue_lng', 10, 7)->nullable();
            $table->text('dress_code_description')->nullable();
            $table->string('dress_code_image_path')->nullable();
            $table->date('rsvp_deadline')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_settings');
    }
};
