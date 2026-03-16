<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class admin_backup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'admin_backup';
    protected $primaryKey = 'backup_id';

    const TYPE_DATABASE = 1;
    const TYPE_FILES    = 2;
    const TYPE_FULL     = 3;
    const TYPE_RESTORE  = 4;

    protected $fillable = [
        'backup_type',
        'backup_size',
        'status',
        'backup_path',
        'file_name',
    ];

    protected $casts = [
        'backup_type' => 'integer',
        'backup_size' => 'integer',
        'status'      => 'string',
    ];
}