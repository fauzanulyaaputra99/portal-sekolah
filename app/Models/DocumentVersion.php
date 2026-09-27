<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'version_number',
        'gdrive_file_id',
        'original_filename',
        'file_size_bytes',
        'mime_type',
        'file_hash_sha256',
        'uploaded_by_user_id',
        'uploader_notes',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DocumentReview::class, 'document_version_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DocumentComment::class, 'document_version_id');
    }
}
