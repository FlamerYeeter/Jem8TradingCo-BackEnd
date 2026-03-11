<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Fetch messages for a chatroom.
     */
    public function index(Request $request)
    {
        $chatroomId = $request->query('chatroom_id');

        if (! $chatroomId) {
            return response()->json(['message' => 'chatroom_id required'], 400);
        }

        $messages = Message::where('chatroom_id', $chatroomId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Store a new message and broadcast it.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'chatroom_id' => 'required|integer',
            'messages' => 'required|string',
            'status' => 'nullable|integer',
            'cart_id' => 'nullable|integer',
        ]);

        $message = Message::create($data);

        event(new NewMessage($message));

        return response()->json($message, 201);
    }
}
