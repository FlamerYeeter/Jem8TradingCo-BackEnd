<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryBlog extends Model
{
    use HasFactory;

    protected $table = 'category_blog';
    protected $primaryKey = 'category_blog_id';

    protected $fillable = [
        'category_name',
        'description',
        'img',
    ];

    // Relationship - if blogs belong to this category
    public function blogs()
    {
        return $this->hasMany(Blog::class, 'category_blog_id', 'category_blog_id');
    }
}