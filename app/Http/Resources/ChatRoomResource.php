<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ChatRoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $last = $this->messages->first();

        $name = null;
        if ($this->user) {
            $nameParts = array_filter([optional($this->user)->first_name, optional($this->user)->last_name]);
            $name = $nameParts ? implode(' ', $nameParts) : null;
        }

        $avatarPath = optional($this->user)->profile_image;

        // Normalize using Laravel's asset helper so the returned URL matches other
        // endpoints (same host/port) and avoids /storage/ double-prefix issues.
        $avatarUrl = $this->normalizeAvatar($avatarPath);

        return [
            'chatroom_id' => $this->chatroom_id,
            'user_id' => $this->user_id,
            'name' => $name ?? ($this->name ?? null),
            'email' => optional($this->user)->email ?? null,
            'avatarUrl' => $avatarUrl ?? asset('images/default-avatar.svg'),
            'product' => $this->product ? [
                'product_id' => $this->product->product_id,
                'product_name' => $this->product->product_name,
                'product_image' => $this->product->primary_image_url ?? null,
            ] : null,
            'preview' => $last ? $last->messages : null,
            'last_time' => $last ? $last->created_at : $this->updated_at,
            'messages_count' => $this->messages_count ?? $this->messages()->count(),
            'status' => $this->status,
            'last_message' => $last ? [
                'message_id' => $last->message_id,
                'messages' => $last->messages,
                'user_id' => $last->user_id,
                'created_at' => $last->created_at,
            ] : null,
        ];
    }

    /**
     * Normalize avatar path helper (kept for future reuse).
     */
    protected function normalizeAvatar(?string $path): ?string
    {
        if (! $path) return null;
        if (Str::startsWith($path, ['http://', 'https://'])) return $path;
        return asset('storage/' . ltrim($path, '/'));
    }
}
