<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasFactory;

    protected $table = 'user_addresses';

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'company_name',
        'company_role',
        'company_number',
        'company_email',
        'address',
        'status',
    ];

    /**
     * Relationship: Address belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(Account::class, 'user_id');
    }
}