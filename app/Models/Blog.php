<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blog extends Model
{
    use HasFactory;

    protected $table = 'blog';
    protected $primaryKey = 'blog_id';

    protected $fillable = [
        'category_blog_id',
        'blog_title',
        'blog_text',
        'featured_image',
        'status',
        'update_at',
        'updated_by',
    ];

    protected $casts = [
        
        'update_at' => 'datetime',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(CategoryBlog::class, 'category_blog_id', 'category_blog_id');
    }
    public function images(): HasMany
    {
        return $this->hasMany(BlogImg::class, 'blog_id', 'blog_id');
    }
}