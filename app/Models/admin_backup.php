<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class admin_backup extends Model
{
    use HasFactory;

    protected $table = 'admin_backup';
    protected $primaryKey = 'backup_id';

    protected $fillable = [
        'backup_type',
        'backup_size',
        'status',
    ];

    protected $casts = [
        'backup_type' => 'integer',
        'backup_size' => 'integer',
        'status'      => 'string',
    ];
}