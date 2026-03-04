<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveChat extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'live_chats';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'chatroom_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'status',
    ];

    /**
     * Get the user that owns this live chat room.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all messages in this live chat room.
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'chatroom_id', 'chatroom_id');
    }
}