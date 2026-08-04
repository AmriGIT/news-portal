<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'news_import_id',
    'source_id',
    'requested_url',
    'final_url',
    'publisher',
    'title',
    'author',
    'published_at',
    'retrieved_at',
    'sha256',
])]
class NewsImportSource extends Model
{
    /**
     * @return BelongsTo<NewsImport, $this>
     */
    public function newsImport(): BelongsTo
    {
        return $this->belongsTo(NewsImport::class);
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'retrieved_at' => 'datetime',
        ];
    }
}
