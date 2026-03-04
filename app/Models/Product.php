<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'product_id';
    protected $table = 'products';

    protected $fillable = [
        'product_name',
        'category_id',
        'product_stocks',
        'description',
        'price',
        'isSale',
        'reviews_id',
    ];

    protected $casts = [
        'isSale' => 'boolean',
        'price' => 'decimal:2'
    ];

    protected $appends = ['primary_image_url']; // Add this

    // Relationship with Category
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    // Relationship with Images
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'product_id');
    }

    // Accessor for primary image URL
    public function getPrimaryImageUrlAttribute()
    {
        $primaryImage = $this->images->where('is_primary', true)->first();
        return $primaryImage ? $primaryImage->image_url : null;
    }

    // A product can have many cart items
    public function carts()
    {
        return $this->hasMany(Cart::class, 'product_id', 'product_id');
    }
}
