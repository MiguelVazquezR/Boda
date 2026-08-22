<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            // Nuestra Historia — Parte 1: Cómo nos conocimos
            $table->text('how_we_met_story')->nullable()->after('our_story');
            $table->string('how_we_met_photo_path')->nullable()->after('how_we_met_story');

            // Nuestra Historia — Parte 2: La propuesta
            $table->text('proposal_story')->nullable()->after('how_we_met_photo_path');
            $table->string('proposal_photo_path')->nullable()->after('proposal_story');

            // Ceremonia
            $table->string('ceremony_title')->nullable()->after('venue_lng');
            $table->string('ceremony_address')->nullable()->after('ceremony_title');
            $table->decimal('ceremony_lat', 10, 7)->nullable()->after('ceremony_address');
            $table->decimal('ceremony_lng', 10, 7)->nullable()->after('ceremony_lat');
            $table->string('ceremony_photo_path')->nullable()->after('ceremony_lng');

            // Celebración
            $table->string('celebration_title')->nullable()->after('ceremony_photo_path');
            $table->string('celebration_address')->nullable()->after('celebration_title');
            $table->decimal('celebration_lat', 10, 7)->nullable()->after('celebration_address');
            $table->decimal('celebration_lng', 10, 7)->nullable()->after('celebration_lat');
            $table->string('celebration_photo_path')->nullable()->after('celebration_lng');

            // Código de vestimenta
            $table->text('dress_code_general')->nullable()->after('dress_code_image_path');
            $table->string('dress_code_women_dress')->nullable()->after('dress_code_general');
            $table->string('dress_code_women_shoes')->nullable()->after('dress_code_women_dress');
            $table->string('dress_code_women_accessories')->nullable()->after('dress_code_women_shoes');
            $table->string('dress_code_women_other')->nullable()->after('dress_code_women_accessories');
            $table->string('dress_code_men_suit')->nullable()->after('dress_code_women_other');
            $table->string('dress_code_men_shoes')->nullable()->after('dress_code_men_suit');
            $table->string('dress_code_men_accessories')->nullable()->after('dress_code_men_shoes');
            $table->string('dress_code_men_other')->nullable()->after('dress_code_men_accessories');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            $table->dropColumn([
                'how_we_met_story', 'how_we_met_photo_path',
                'proposal_story', 'proposal_photo_path',
                'ceremony_title', 'ceremony_address', 'ceremony_lat', 'ceremony_lng', 'ceremony_photo_path',
                'celebration_title', 'celebration_address', 'celebration_lat', 'celebration_lng', 'celebration_photo_path',
                'dress_code_general',
                'dress_code_women_dress', 'dress_code_women_shoes', 'dress_code_women_accessories', 'dress_code_women_other',
                'dress_code_men_suit', 'dress_code_men_shoes', 'dress_code_men_accessories', 'dress_code_men_other',
            ]);
        });
    }
};
