<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkout extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'checkouts';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'checkout_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'cart_id',
        'discount_id',
        'payment_method',
        'payment_reference',
        'shipping_fee',
        'paid_amount',
        'paid_at',
        'special_instructions',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'shipping_fee' => 'double',
        'paid_amount'  => 'double',
        'paid_at'      => 'datetime',
    ];

    /**
     * Get the user that owns this checkout.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the cart associated with this checkout.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    /**
     * Get the discount applied to this checkout.
     */
    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }
}