<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'messages';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'message_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'chatroom_id',
        'user_id',
        'messages',
        'status',
        'sender',
        'cart_id',
    ];

    /**
     * Get the chat room this message belongs to.
     */
    public function chatRoom()
    {
        return $this->belongsTo(LiveChat::class, 'chatroom_id', 'chatroom_id');
    }

    /**
     * Get the user that sent this message.
     */
    public function user()
    {
        return $this->belongsTo(Account::class, 'user_id');
    }

    /**
     * Attachments uploaded with this message.
     */
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'message_id', 'message_id');
    }
}