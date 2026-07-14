<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WeddingSetting extends Model
{
    protected $table = 'wedding_settings';

    protected $fillable = [
        'cover_photo_path',
        'our_story',
        'how_we_met_story',
        'how_we_met_photo_path',
        'proposal_story',
        'proposal_photo_path',
        'event_datetime',
        'venue_name',
        'venue_address',
        'venue_lat',
        'venue_lng',
        'ceremony_title',
        'ceremony_datetime',
        'ceremony_address',
        'ceremony_lat',
        'ceremony_lng',
        'ceremony_photo_path',
        'celebration_title',
        'celebration_datetime',
        'celebration_address',
        'celebration_lat',
        'celebration_lng',
        'celebration_photo_path',
        'dress_code_description',
        'dress_code_image_path',
        'dress_code_general',
        'dress_code_women_dress',
        'dress_code_women_dress_desc',
        'dress_code_women_shoes',
        'dress_code_women_shoes_desc',
        'dress_code_women_accessories',
        'dress_code_women_accessories_desc',
        'dress_code_women_other',
        'dress_code_women_other_desc',
        'dress_code_men_suit',
        'dress_code_men_suit_desc',
        'dress_code_men_shoes',
        'dress_code_men_shoes_desc',
        'dress_code_men_accessories',
        'dress_code_men_accessories_desc',
        'dress_code_men_other',
        'dress_code_men_other_desc',
        'rsvp_deadline',
    ];

    /**
     * Accessors de URL que deben incluirse en la serialización JSON
     * para que Inertia/Vue los reciba correctamente.
     */
    protected $appends = [
        'cover_photo_url',
        'dress_code_image_url',
        'how_we_met_photo_url',
        'proposal_photo_url',
        'ceremony_photo_url',
        'celebration_photo_url',
        'dress_code_women_dress_url',
        'dress_code_women_shoes_url',
        'dress_code_women_accessories_url',
        'dress_code_women_other_url',
        'dress_code_men_suit_url',
        'dress_code_men_shoes_url',
        'dress_code_men_accessories_url',
        'dress_code_men_other_url',
    ];

    protected function casts(): array
    {
        return [
            'event_datetime' => 'datetime',
            'ceremony_datetime' => 'datetime',
            'celebration_datetime' => 'datetime',
            'rsvp_deadline' => 'date',
        ];
    }

    /**
     * Singleton: siempre devuelve la primera (y única) fila, creándola con defaults si no existe.
     */
    public static function current(): self
    {
        return static::firstOrCreate([], [
            'event_datetime' => '2026-11-14 16:00:00',
            'venue_name' => 'Por definir',
            'venue_address' => 'Por definir',
        ]);
    }

    /**
     * Accessor: URL pública completa de la foto de portada.
     */
    public function getCoverPhotoUrlAttribute(): ?string
    {
        return $this->cover_photo_path
            ? asset('storage/' . $this->cover_photo_path)
            : null;
    }

    /**
     * Accessor: URL pública de la imagen de código de vestimenta.
     */
    public function getDressCodeImageUrlAttribute(): ?string
    {
        return $this->dress_code_image_path
            ? asset('storage/' . $this->dress_code_image_path)
            : null;
    }

    // ── Accessors genéricos para nuevas fotos ──

    public function getHowWeMetPhotoUrlAttribute(): ?string
    { return $this->urlOrNull($this->how_we_met_photo_path); }

    public function getProposalPhotoUrlAttribute(): ?string
    { return $this->urlOrNull($this->proposal_photo_path); }

    public function getCeremonyPhotoUrlAttribute(): ?string
    { return $this->urlOrNull($this->ceremony_photo_path); }

    public function getCelebrationPhotoUrlAttribute(): ?string
    { return $this->urlOrNull($this->celebration_photo_path); }

    public function getDressCodeWomenDressUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_women_dress); }

    public function getDressCodeWomenShoesUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_women_shoes); }

    public function getDressCodeWomenAccessoriesUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_women_accessories); }

    public function getDressCodeWomenOtherUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_women_other); }

    public function getDressCodeMenSuitUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_men_suit); }

    public function getDressCodeMenShoesUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_men_shoes); }

    public function getDressCodeMenAccessoriesUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_men_accessories); }

    public function getDressCodeMenOtherUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_men_other); }

    private function urlOrNull(?string $path): ?string
    {
        return $path ? asset('storage/' . $path) : null;
    }
}
