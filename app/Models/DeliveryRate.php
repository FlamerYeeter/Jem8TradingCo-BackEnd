<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryRate extends Model
{
    use HasFactory;

    protected $table = 'delivery_rates';

    protected $fillable = [
        'province',
        'city',
        'barangay',
        'fee',
        'note',
    ];

    protected $casts = [
        'fee' => 'double',
    ];
}
