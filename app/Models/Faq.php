<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $table = 'faqs';

    protected $fillable = [
        'question',
        'answer',
        'order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Scope: solo FAQs publicadas, ordenadas por el campo `order`.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)->orderBy('order');
    }
}
