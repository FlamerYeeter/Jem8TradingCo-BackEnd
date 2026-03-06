<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';
    protected $primaryKey = 'invoice_id';

    protected $fillable = [
        'user_id',
        'checkout_id',
        'receipt_id',
        'invoice_number',
        'billing_address',
        'tax_amount',
        'total_amount',
        'status',
        'issued_at',
    ];

    protected $dates = [
        'issued_at',
        'created_at',
        'updated_at',
    ];
}
