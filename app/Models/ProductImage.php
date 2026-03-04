<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $primaryKey = 'image_id';
    protected $table = 'product_images';

    protected $fillable = [
        'product_id',
        'image_path',
        'is_primary',  // Add this
    ];

    protected $casts = [
        'is_primary' => 'boolean',  // Cast to boolean
    ];

    protected $appends = ['image_url'];  // Auto-add image_url to JSON

    // Accessor for image URL
    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            // Return full URL
            return asset('storage/' . $this->image_path);
        }
        return null;
    }

    // Each image belongs to a product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
