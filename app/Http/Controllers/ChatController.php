<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Http\Resources\ChatRoomResource;
use App\Models\Message;
use App\Models\LiveChat;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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

        $messages = Message::with(['user:id,first_name,last_name,profile_image', 'attachments'])
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

                // include attachments payload
                $attachmentsPayload = [];
                foreach ($m->attachments ?? [] as $att) {
                    $attachmentsPayload[] = [
                        'id' => $att->id,
                        'url' => asset('storage/' . ltrim($att->path, '/')),
                        'filename' => $att->filename,
                        'mime' => $att->mime,
                        'size' => $att->size,
                        'thumbnail_url' => $att->thumbnail_path ? asset('storage/' . ltrim($att->thumbnail_path, '/')) : null,
                    ];
                }

                $m->attachments_payload = $attachmentsPayload;

                return $m;
            });

        return response()->json($messages);
    }

    /**
     * Store a new message and broadcast it.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'chatroom_id' => 'nullable|integer',
            'target_user_id' => 'nullable|integer',
            'messages' => 'nullable|string',
            'text' => 'nullable|string',
            'status' => 'nullable|integer',
            'sender' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'cart_id' => 'nullable|integer',
            'file' => 'sometimes|file',
            'files' => 'sometimes|array',
            'files.*' => 'file',
        ]);

        // Enforce authenticated user as author
        $validated['user_id'] = Auth::id();

        // Choose message text field
        $validated['messages'] = $validated['messages'] ?? $validated['text'] ?? null;

        // Determine chatroom as before
        if (empty($validated['chatroom_id'])) {
            if (Auth::id() === 1 && ! empty($validated['target_user_id'])) {
                $targetUserId = (int) $validated['target_user_id'];
                $userChat = LiveChat::firstOrCreate(
                    ['user_id' => $targetUserId],
                    ['status' => 'active']
                );
                $validated['chatroom_id'] = $userChat->chatroom_id;
            } else {
                $userId = Auth::id();
                $userChat = LiveChat::firstOrCreate(
                    ['user_id' => $userId],
                    ['status' => 'active']
                );
                $validated['chatroom_id'] = $userChat->chatroom_id;
            }
        }

        // File handling rules
        $maxFiles = 5;
        $allowedMimeStarts = ['image/', 'video/'];
        $allowedExact = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
        ];
        $imageMax = 10 * 1024 * 1024; // 10 MB
        $videoMax = 50 * 1024 * 1024; // 50 MB
        $docMax = 25 * 1024 * 1024; // 25 MB

        $uploadedFiles = [];
        if ($request->hasFile('file')) {
            $uploadedFiles[] = $request->file('file');
        }
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $f) {
                $uploadedFiles[] = $f;
            }
        }

        if (count($uploadedFiles) > $maxFiles) {
            return response()->json(['message' => 'Too many files (max '.$maxFiles.')'], 413);
        }

        // Transaction: create message, store files metadata
        $message = DB::transaction(function () use ($validated, $uploadedFiles, $allowedMimeStarts, $allowedExact, $imageMax, $videoMax, $docMax) {
            $message = Message::create($validated);

            $attachments = [];

            foreach ($uploadedFiles as $file) {
                if (! $file->isValid()) {
                    throw new \Exception('Uploaded file invalid');
                }

                $mime = $file->getMimeType();
                $size = $file->getSize();

                // Validate mime/size
                $accepted = false;
                foreach ($allowedMimeStarts as $prefix) {
                    if (str_starts_with($mime, $prefix)) {
                        $accepted = true;
                        if (str_starts_with($mime, 'image/')) {
                            if ($size > $imageMax) {
                                return response()->json(['message' => 'Image too large'], 413);
                            }
                        } elseif (str_starts_with($mime, 'video/')) {
                            if ($size > $videoMax) {
                                return response()->json(['message' => 'Video too large'], 413);
                            }
                        }
                        break;
                    }
                }
                if (! $accepted && in_array($mime, $allowedExact, true) === false) {
                    return response()->json(['message' => 'Unsupported media type: '.$mime], 415);
                }
                if (! $accepted && in_array($mime, $allowedExact, true)) {
                    // document size check
                    if ($size > $docMax) {
                        return response()->json(['message' => 'Document too large'], 413);
                    }
                }

                // Safe store
                $year = now()->format('Y');
                $month = now()->format('m');
                $chatroom = $validated['chatroom_id'];
                // store on the `public` disk so files land in storage/app/public/...
                $dir = "chat/{$chatroom}/{$year}/{$month}";
                $storedName = (string) Str::uuid() .'.'. $file->getClientOriginalExtension();

                Storage::disk('public')->putFileAs($dir, $file, $storedName);

                $relativePath = "{$dir}/{$storedName}";

                $attachment = Attachment::create([
                    'chatroom_id' => $chatroom,
                    'message_id' => $message->getKey(),
                    'user_id' => $message->user_id,
                    'filename' => $file->getClientOriginalName(),
                    'stored_name' => $storedName,
                    'mime' => $mime,
                    'size' => $size,
                    'path' => $relativePath,
                    'thumbnail_path' => null,
                    'processing_status' => 'pending',
                ]);

                $attachments[] = $attachment;
            }

            // attach attachments relationship in-memory (do not assign property directly)
            $message->setRelation('attachments', collect($attachments));

            return $message;
        });

        // Broadcast
        event(new NewMessage($message));

        // Format response payload with attachments metadata
        $attachmentsPayload = [];
        foreach ($message->attachments ?? [] as $att) {
            $attachmentsPayload[] = [
                'id' => $att->id,
                'url' => asset('storage/' . ltrim($att->path, '/')),
                'filename' => $att->filename,
                'mime' => $att->mime,
                'size' => $att->size,
                'thumbnail_url' => $att->thumbnail_path ? asset('storage/' . ltrim($att->thumbnail_path, '/')) : null,
            ];
        }

        $response = [
            'chatroom_id' => $validated['chatroom_id'],
            'message' => [
                'id' => $message->getKey(),
                'text' => $message->messages,
                'attachments' => $attachmentsPayload,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ];

        return response()->json(['data' => $response], 201);
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
        $messages = $messages->load(['user:id,first_name,last_name,profile_image', 'attachments'])
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

                $attachmentsPayload = [];
                foreach ($m->attachments ?? [] as $att) {
                    $attachmentsPayload[] = [
                        'id' => $att->id,
                        'url' => asset('storage/' . ltrim($att->path, '/')),
                        'filename' => $att->filename,
                        'mime' => $att->mime,
                        'size' => $att->size,
                        'thumbnail_url' => $att->thumbnail_path ? asset('storage/' . ltrim($att->thumbnail_path, '/')) : null,
                    ];
                }

                $m->attachments_payload = $attachmentsPayload;

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
