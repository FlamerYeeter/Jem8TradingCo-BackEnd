<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class About extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'abouts';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'about_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'mission',
        'vission',
        'leadership_id',
        'about_desc',
    ];

    /**
     * Get the leadership record associated with this about.
     */
    public function leadership()
    {
        return $this->belongsTo(admin_leadership::class, 'leadership_id', 'leadership_id');
    }
}