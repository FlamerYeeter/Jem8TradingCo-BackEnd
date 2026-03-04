<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'discounts';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'discount_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'discount_type',
        'value',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'value'      => 'double',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get all checkouts using this discount.
     */
    public function checkouts()
    {
        return $this->hasMany(Checkout::class, 'discount_id', 'discount_id');
    }

    /**
     * Check if the discount is still valid/not expired.
     */
    public function isValid(): bool
    {
        return is_null($this->expires_at) || $this->expires_at->isFuture();
    }
}