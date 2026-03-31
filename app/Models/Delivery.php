<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $table = 'deliveries';
    protected $primaryKey = 'delivery_id';

    protected $fillable = [
        'checkout_id',
        'status',
        'driver_id',
        'notes',
    ];

    // Append human-friendly status fields for frontend mapping
    protected $appends = ['status_label', 'status_step'];

    public function checkout()
    {
        return $this->belongsTo(Checkout::class, 'checkout_id');
    }

    // Map internal status values to UI labels and step indices
    public function getStatusLabelAttribute()
    {
        $map = [
            'pending'    => 'Ordered',
            'processing' => 'Confirmed',
            'ready'      => 'Packed',
            'on_the_way' => 'Packed',
            'shipped'    => 'Packed',
            'delivered'  => 'Delivered',
            'cancelled'  => 'Cancelled',
        ];

        return $map[$this->status] ?? 'Ordered';
    }

    public function getStatusStepAttribute()
    {
        $steps = [
            'Ordered'   => 1,
            'Confirmed' => 2,
            'Packed'    => 3,
            'Delivered' => 4,
            'Cancelled' => 0,
        ];

        $label = $this->status_label;
        return $steps[$label] ?? 1;
    }
}