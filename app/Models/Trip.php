<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id', 'start_lat', 'start_lng', 'dest_lat', 'dest_lng', 'dest_address'
    ];

    protected $casts = [
        'start_lat' => 'float',
        'start_lng' => 'float',
        'dest_lat' => 'float',
        'dest_lng' => 'float',
    ];
}
