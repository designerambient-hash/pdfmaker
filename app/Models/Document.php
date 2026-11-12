<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Document extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'user_id',
        'session_id',
        'title',
        'description',
        'page_size',
        'unit',
        'width',
        'height',
        'orientation',
        'is_starred',
        'status',
        'thumbnail',
        'settings',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'float',
            'height' => 'float',
            'is_starred' => 'boolean',
            'settings' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(DocumentPage::class)->orderBy('page_number');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(DocumentAsset::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version_number');
    }

    /**
     * Duplicate this document with all pages and independent copy.
     */
    public function duplicate(): self
    {
        $clone = $this->replicate();
        $clone->id = (string) Str::uuid();
        $clone->title = 'Copy of ' . $this->title;
        $clone->is_starred = false;
        $clone->status = 'active';
        $clone->created_at = now();
        $clone->updated_at = now();
        $clone->save();

        foreach ($this->pages as $page) {
            $newPage = $page->replicate();
            $newPage->document_id = $clone->id;
            $newPage->created_at = now();
            $newPage->updated_at = now();
            $newPage->save();
        }

        return $clone;
    }
}

