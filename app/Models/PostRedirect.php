<?php

namespace App\Models;

use Database\Factories\PostRedirectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'post_id',
    'source_path',
    'destination_path',
    'status_code',
    'is_active',
    'hit_count',
    'last_hit_at',
    'old_slug',
])]
class PostRedirect extends Model
{
    /** @use HasFactory<PostRedirectFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hit_count' => 'integer',
            'is_active' => 'boolean',
            'last_hit_at' => 'datetime',
            'status_code' => 'integer',
        ];
    }
}
