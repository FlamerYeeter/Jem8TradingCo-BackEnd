<?php

namespace App\Services;

use App\Models\Notifications;
use App\Events\NotificationCreated;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    /**
     * Create a notification row and broadcast it.
     *
     * @param int $userId
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @return Notifications|null
     */
    public static function createAndBroadcast(int $userId, string $type, string $title, string $message, ?string $referenceType = null, ?int $referenceId = null): ?Notifications
    {
        try {
            $notif = Notifications::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'reference_type' => $referenceType,
                'is_read' => false,
            ]);

            // Optionally add reference id if model supports it (not all migrations include it)
            if ($referenceId && isset($notif->reference_id)) {
                $notif->reference_id = $referenceId;
                $notif->save();
            }

            // Broadcast the notification event
            event(new NotificationCreated($notif));

            // Clear dashboard cache so counts update
            Cache::forget('dashboard.notifications');

            return $notif;
        } catch (\Exception $e) {
            // Log if needed, but do not throw to avoid breaking caller
            logger()->error('NotificationService::createAndBroadcast failed: ' . $e->getMessage());
            return null;
        }
    }
}
