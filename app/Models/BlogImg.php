<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogImg extends Model
{
    protected $fillable = [
        'blog_id',
        'url',
        'alt_text',
        'order',
    ];
    

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }
}