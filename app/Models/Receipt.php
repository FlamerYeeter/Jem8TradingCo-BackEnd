<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;

    protected $table = 'receipts';
    protected $primaryKey = 'receipt_id';

    protected $fillable = [
        'user_id',
        'checkout_id',
        'receipt_number',
        'payment_method',
        'payment_reference',
        'paid_amount',
        'paid_at',
    ];

    protected $casts = [
        'paid_amount' => 'double',
        'paid_at'     => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function checkout()
    {
        return $this->belongsTo(Checkout::class, 'checkout_id', 'checkout_id');
    }
}
