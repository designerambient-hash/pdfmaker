<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'category',
        'description',
        'page_size',
        'width',
        'height',
        'orientation',
        'thumbnail',
        'document_data',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'float',
            'height' => 'float',
            'is_system' => 'boolean',
            'document_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

