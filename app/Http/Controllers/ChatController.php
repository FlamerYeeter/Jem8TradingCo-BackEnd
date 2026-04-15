<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Http\Resources\ChatRoomResource;
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

                // compute avatarUrl for message sender (mirrors ChatRoomResource behavior)
                $avatarPath = optional($m->user)->profile_image;
                $avatarUrl = null;
                if ($avatarPath) {
                    if (str_starts_with($avatarPath, 'http')) {
                        $avatarUrl = $avatarPath;
                    } else {
                        $avatarUrl = asset('storage/' . ltrim($avatarPath, '/'));
                    }
                }
                $m->avatarUrl = $avatarUrl ?? asset('images/default-avatar.svg');

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
            'target_user_id' => 'nullable|integer',
            'messages' => 'required|string',
            'status' => 'nullable|integer',
            'sender' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'cart_id' => 'nullable|integer',
        ]);

        // Enforce the authenticated user as the message author to prevent spoofing.
        $data['user_id'] = Auth::id();

        // If no chatroom provided, decide which user's chatroom to use.
        // - For admin (user id 1) allow sending to a specific member via `target_user_id`.
        // - For regular users, create/return their personal chatroom.
        if (empty($data['chatroom_id'])) {
            if (Auth::id() === 1 && ! empty($data['target_user_id'])) {
                $targetUserId = (int) $data['target_user_id'];
                $userChat = LiveChat::firstOrCreate(
                    ['user_id' => $targetUserId],
                    ['status' => 'active']
                );
                $data['chatroom_id'] = $userChat->chatroom_id;
            } else {
                $userId = Auth::id();
                $userChat = LiveChat::firstOrCreate(
                    ['user_id' => $userId],
                    ['status' => 'active']
                );
                $data['chatroom_id'] = $userChat->chatroom_id;
            }
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

                // compute avatarUrl for message sender (mirrors ChatRoomResource behavior)
                $avatarPath = optional($m->user)->profile_image;
                $avatarUrl = null;
                if ($avatarPath) {
                    if (str_starts_with($avatarPath, 'http')) {
                        $avatarUrl = $avatarPath;
                    } else {
                        $avatarUrl = asset('storage/' . ltrim($avatarPath, '/'));
                    }
                }
                $m->avatarUrl = $avatarUrl ?? asset('images/default-avatar.svg');

                return $m;
            });

        return response()->json($messages);
    }

    /**
     * Return available chat rooms.
     */
    public function rooms()
    {
        // Eager-load user and the latest message for a clean front-end payload.
        $query = LiveChat::with(['user:id,first_name,last_name,email,profile_image', 'product:product_id,product_name', 'messages' => function ($q) {
            $q->orderBy('created_at', 'desc')->limit(1);
        }])->withCount('messages')
        ->orderBy('chatroom_id', 'asc');

        if (Auth::id() !== 1) {
            $query = $query->where('user_id', Auth::id());
        }

        $rooms = $query->get();

        return response()->json(ChatRoomResource::collection($rooms));
    }

    /**
     * Return chat rooms with latest message and account info for display lists.
     */
    public function roomsSummary()
    {
        // Provide the same enriched payload as `rooms` but ordered for summaries.
        $query = LiveChat::with(['user:id,first_name,last_name,email,profile_image', 'product:product_id,product_name', 'messages' => function ($q) {
            $q->orderBy('created_at', 'desc')->limit(1);
            }])->withCount('messages')
            ->orderBy('chatroom_id', 'desc');

        if (Auth::id() !== 1) {
            $query = $query->where('user_id', Auth::id());
        }

        $rooms = $query->get();

        return response()->json(ChatRoomResource::collection($rooms));
    }
}
