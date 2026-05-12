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
    'delivery_street',
    'delivery_barangay',
    'delivery_city',
    'delivery_province',
    'delivery_zip',
    'delivery_country',
    'delivery_type',
];

protected $casts = [
    'payment_details' => 'array',
    // ❌ remove 'delivery_address' => 'array' if still there
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
    public function receipt()
    {
        return $this->hasOne(\App\Models\Receipt::class, 'checkout_id', 'checkout_id');
    }
    public function items()
    {
        return $this->hasMany(\App\Models\CheckoutItem::class, 'checkout_id', 'checkout_id');
    }
}
