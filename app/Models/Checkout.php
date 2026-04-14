<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Account;

class Checkout extends Model
{
    use HasFactory;

    protected $table      = 'checkouts';
    protected $primaryKey = 'checkout_id';

    protected $fillable = [
        'user_id',
        'cart_id',
        'discount_id',
        'payment_method',
        'payment_details',
        'shipping_fee',
        'paid_amount',
        'paid_at',
        'special_instructions',
        'delivery_address',
    ];

    protected $casts = [
        'payment_details' => 'array',
        'delivery_address' => 'array',
        'shipping_fee'    => 'double',
        'paid_amount'     => 'double',
        'paid_at'         => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(Account::class, 'user_id');
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
