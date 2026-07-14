<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            // Fechas separadas para ceremonia y celebración
            $table->dateTime('ceremony_datetime')->nullable()->after('ceremony_title');
            $table->dateTime('celebration_datetime')->nullable()->after('celebration_title');

            // Descripciones para cada foto de dress code
            $table->string('dress_code_women_dress_desc')->nullable()->after('dress_code_women_dress');
            $table->string('dress_code_women_shoes_desc')->nullable()->after('dress_code_women_shoes');
            $table->string('dress_code_women_accessories_desc')->nullable()->after('dress_code_women_accessories');
            $table->string('dress_code_women_other_desc')->nullable()->after('dress_code_women_other');

            $table->string('dress_code_men_suit_desc')->nullable()->after('dress_code_men_suit');
            $table->string('dress_code_men_shoes_desc')->nullable()->after('dress_code_men_shoes');
            $table->string('dress_code_men_accessories_desc')->nullable()->after('dress_code_men_accessories');
            $table->string('dress_code_men_other_desc')->nullable()->after('dress_code_men_other');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ceremony_datetime', 'celebration_datetime',
                'dress_code_women_dress_desc', 'dress_code_women_shoes_desc',
                'dress_code_women_accessories_desc', 'dress_code_women_other_desc',
                'dress_code_men_suit_desc', 'dress_code_men_shoes_desc',
                'dress_code_men_accessories_desc', 'dress_code_men_other_desc',
            ]);
        });
    }
};
