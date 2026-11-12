<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'page_number',
        'width',
        'height',
        'background_type',
        'background_color',
        'background_gradient',
        'background_image',
        'background_pattern',
        'background_opacity',
        'border_color',
        'border_width',
        'border_style',
        'margins',
        'canvas_data',
        'thumbnail',
    ];

    protected function casts(): array
    {
        return [
            'page_number' => 'integer',
            'width' => 'float',
            'height' => 'float',
            'background_opacity' => 'float',
            'border_width' => 'float',
            'background_gradient' => 'array',
            'margins' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}

