<?php

namespace App\Models;

use App\Enums\NewsImportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'uuid',
    'user_id',
    'import_token_id',
    'original_filename',
    'package_hash',
    'idempotency_key',
    'requested_publish_mode',
    'status',
    'content_mode',
    'image_mode',
    'total_items',
    'valid_items',
    'invalid_items',
    'imported_items',
    'failed_items',
    'warnings',
    'response_payload',
    'error_message',
    'ip_address',
    'user_agent',
    'started_at',
    'completed_at',
])]
class NewsImport extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ImportToken, $this>
     */
    public function importToken(): BelongsTo
    {
        return $this->belongsTo(ImportToken::class);
    }

    /**
     * @return HasMany<NewsImportItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(NewsImportItem::class);
    }

    /**
     * @return HasMany<NewsImportSource, $this>
     */
    public function sources(): HasMany
    {
        return $this->hasMany(NewsImportSource::class);
    }

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'started_at' => 'datetime',
            'status' => NewsImportStatus::class,
            'warnings' => 'array',
            'response_payload' => 'array',
        ];
    }
}
