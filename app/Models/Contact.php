<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'contacts';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'message_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sender',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'message',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who sent this message.
     */
    public function senderUser()
    {
        return $this->belongsTo(User::class, 'sender');
    }
}