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
        'canva_url',
        'gift_registry_url',
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
     * Serializa las fechas conservando la zona horaria del lugar del evento
     * (America/Mexico_City).
     *
     * Eloquent convierte por defecto a UTC ("...Z"), lo que hacía que la hora de la
     * ceremonia / celebración se mostrara desfasada en la invitación. Con este formato
     * se envía "2026-11-14T20:50:00-06:00", es decir la hora tal como se configuró.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:sP');
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
     * URL del sitio de Canva con la invitación animada (música y transiciones).
     * Si no se configuró desde el panel, se usa el valor por defecto de config/wedding.php.
     */
    public function invitationArtworkUrl(): string
    {
        return (string) ($this->canva_url ?: config('wedding.canva_url'));
    }

    /**
     * Link de la mesa de regalos (lista de sugerencias en Amazon).
     * Si no se configuró desde el panel, se usa el valor por defecto de config/wedding.php.
     */
    public function giftRegistryUrl(): string
    {
        return (string) ($this->gift_registry_url ?: config('wedding.gift_registry_url'));
    }

    /**
     * Nombres de los novios tal como se muestran en la invitación.
     */
    public static function coupleNames(): string
    {
        return (string) config('wedding.couple_names');
    }

    /**
     * Nombres cortos de los novios ("José y Elizabeth") para textos
     * informales como el mensaje de WhatsApp: primer nombre de cada uno.
     */
    public static function coupleFirstNames(): string
    {
        $parts = array_values(array_filter(array_map('trim', preg_split('/\s*&\s*/u', static::coupleNames()) ?: [])));

        if (count($parts) !== 2) {
            return static::coupleNames();
        }

        $firstNames = array_map(
            fn (string $name) => preg_split('/\s+/u', $name)[0] ?? $name,
            $parts,
        );

        return implode(' y ', $firstNames);
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
