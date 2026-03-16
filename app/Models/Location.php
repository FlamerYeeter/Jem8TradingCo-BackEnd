<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'user_id',
        'lat',
        'lng',
        'accuracy',
        'speed',
        'bearing',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'accuracy' => 'float',
        'speed' => 'float',
        'bearing' => 'float',
    ];
}
