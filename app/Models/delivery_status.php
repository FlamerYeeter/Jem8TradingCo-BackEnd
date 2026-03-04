<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class delivery_status extends Model
{
    use HasFactory;

    protected $table = 'delivery_status';
    protected $primaryKey = 'delivery_id';

    protected $fillable = [
        'user_id',
        'checkout_id',
        'status',
        'location',
        'remarks',
    ];

    protected $casts = [
        'status' => 'string',
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