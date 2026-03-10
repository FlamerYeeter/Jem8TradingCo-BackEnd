<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $table = 'user_addresses';

    protected $primaryKey = 'user_address_id';

    protected $fillable = [
        'user_id',
        'company_name',
        'company_role',
        'company_number',
        'company_email',
        'address',
        'status'
    ];
}