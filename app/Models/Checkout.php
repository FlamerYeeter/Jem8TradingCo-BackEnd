<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkout extends Model
{
    use HasFactory;

    protected $table      = 'checkouts';
    protected $primaryKey = 'checkout_id';

    protected $fillable = [
        'user_id',
        'cart_id',              // single cart
        'discount_id',
        'payment_method',
        'payment_details',
        'shipping_fee',
        'paid_amount',
        'paid_at',
        'special_instructions',
    ];

    protected $casts = [
        'payment_details' => 'array',
        'shipping_fee'    => 'double',
        'paid_amount'     => 'double',
        'paid_at'         => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }
    public function delivery()
    {
        return $this->hasOne(Delivery::class, 'checkout_id');
    }
}