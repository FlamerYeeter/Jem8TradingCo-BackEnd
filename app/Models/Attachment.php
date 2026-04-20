<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'chatroom_id',
        'message_id',
        'user_id',
        'filename',
        'stored_name',
        'mime',
        'size',
        'path',
        'thumbnail_path',
        'processing_status',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\Account::class, 'user_id');
    }
}
