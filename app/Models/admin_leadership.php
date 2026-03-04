<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class admin_leadership extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'admin_leaderships';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'leadership_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'position',
        'status',
        'leadership_img',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get the user that owns this leadership record.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
