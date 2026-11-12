<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFont extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'font_name',
        'font_family',
        'font_weight',
        'font_style',
        'font_file_path',
        'font_format',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

