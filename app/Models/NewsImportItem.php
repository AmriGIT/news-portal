<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'news_import_id',
    'post_id',
    'title',
    'slug',
    'requested_status',
    'final_status',
    'source_ids',
    'image_path',
    'validation_errors',
    'warnings',
])]
class NewsImportItem extends Model
{
    /**
     * @return BelongsTo<NewsImport, $this>
     */
    public function newsImport(): BelongsTo
    {
        return $this->belongsTo(NewsImport::class);
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    protected function casts(): array
    {
        return [
            'source_ids' => 'array',
            'validation_errors' => 'array',
            'warnings' => 'array',
        ];
    }
}
