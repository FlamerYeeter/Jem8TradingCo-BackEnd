<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'images',
        'status',
        'update_at',
        'updated_by',
    ];

    protected $casts = [
        'images'    => 'array',
        'update_at' => 'datetime',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(CategoryBlog::class, 'category_blog_id', 'category_blog_id');
    }
}