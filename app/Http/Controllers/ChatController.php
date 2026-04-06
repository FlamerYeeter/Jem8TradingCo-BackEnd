<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Models\Message;
use App\Models\LiveChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $messages = Message::with('user:id,first_name,last_name,profile_image')
            ->where('chatroom_id', $chatroomId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($m) {
                $m->is_me = optional(auth())->id() && $m->user_id == auth()->id();
                return $m;
            });

        return response()->json($messages);
    }

    /**
     * Store a new message and broadcast it.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            // allow omitting chatroom_id; we'll default to admin chatroom below
            'chatroom_id' => 'nullable|integer',
            'messages' => 'required|string',
            'status' => 'nullable|integer',
            'sender' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'cart_id' => 'nullable|integer',
        ]);

        // Enforce the authenticated user as the message author to prevent spoofing.
        $data['user_id'] = Auth::id();

        // If no chatroom provided, ensure the current user has a chatroom and use it.
        if (empty($data['chatroom_id'])) {
            $userId = Auth::id();
            $userChat = LiveChat::firstOrCreate(
                ['user_id' => $userId],
                ['status' => 'active']
            );
            $data['chatroom_id'] = $userChat->chatroom_id;
        }

        // Create message (sender/defaults handled by model/migration)
        $message = Message::create($data);

        event(new NewMessage($message));

        return response()->json($message, 201);
    }

    /**
     * Fetch messages for a chatroom via route parameter.
     */
    public function show($chatroomId, Request $request)
    {
        $limit = $request->query('limit');

        $query = Message::where('chatroom_id', $chatroomId)
            ->orderBy('created_at', 'asc');

        if ($limit) {
            $messages = $query->take((int) $limit)->get();
        } else {
            $messages = $query->get();
        }

        // include sender account and mark which messages belong to current user
        $messages = $messages->load('user:id,first_name,last_name,profile_image')
            ->map(function ($m) {
                $m->is_me = optional(auth())->id() && $m->user_id == auth()->id();
                return $m;
            });

        return response()->json($messages);
    }

    /**
     * Return available chat rooms.
     */
    public function rooms()
    {
        $rooms = LiveChat::withCount('messages')
            ->where('user_id', Auth::id())
            ->orderBy('chatroom_id', 'asc')
            ->get();

        return response()->json($rooms);
    }

    /**
     * Return chat rooms with latest message and account info for display lists.
     */
    public function roomsSummary()
    {
        $rooms = LiveChat::with(['user:id,first_name,last_name,profile_image', 'messages' => function ($q) {
            $q->orderBy('created_at', 'desc')->limit(1);
            }])
            ->where('user_id', Auth::id())
            ->withCount('messages')
            ->orderBy('chatroom_id', 'desc')
            ->get()
            ->map(function ($room) {
                $last = $room->messages->first();
                return [
                    'chatroom_id' => $room->chatroom_id,
                    'account' => $room->user,
                    'last_message' => $last ? [
                        'message_id' => $last->message_id,
                        'messages' => $last->messages,
                        'created_at' => $last->created_at,
                    ] : null,
                    'messages_count' => $room->messages_count,
                    'status' => $room->status,
                ];
            });

        return response()->json($rooms);
    }
}
