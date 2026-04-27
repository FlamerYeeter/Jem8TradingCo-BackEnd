<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Delivery;
use App\Models\Checkout;
use App\Models\Account;
use App\Models\Notifications as NotificationModel;
use App\Events\NotificationCreated;

class TestNotification extends Command
{
    protected $signature = 'notifications:test-update-status {deliveryId} {status}';

    protected $description = 'Test updating a delivery status and creating/broadcasting a notification. Use deliveryId=create to create test records.';

    public function handle()
    {
        $deliveryId = $this->argument('deliveryId');
        $status = $this->argument('status');

        DB::beginTransaction();
        try {
            if ($deliveryId === 'create') {
                $user = Account::create([
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'email' => 'test+' . time() . '@example.com',
                    'password' => bcrypt('secret123'),
                ]);

                $checkout = Checkout::create([
                    'user_id' => $user->id,
                    'payment_method' => 'cash',
                    'shipping_fee' => 0,
                    'paid_amount' => 0,
                ]);

                $delivery = Delivery::create([
                    'checkout_id' => $checkout->checkout_id,
                    'status' => 'processing',
                ]);
            } else {
                $delivery = Delivery::find($deliveryId);
                if (!$delivery) {
                    $this->error("Delivery id {$deliveryId} not found");
                    DB::rollBack();
                    return 1;
                }
                $checkout = $delivery->checkout;
                $user = $checkout?->user;
            }

            $oldStatus = $delivery->status;
            $delivery->status = $status;
            $delivery->save();

            $this->info("Delivery {$delivery->delivery_id} updated: {$oldStatus} -> {$delivery->status}");

            if ($checkout && $checkout->user_id) {
                $notif = NotificationModel::create([
                    'user_id' => $checkout->user_id,
                    'type' => 'order_status',
                    'title' => 'Order status updated (test)',
                    'message' => "Your order #{$checkout->checkout_id} status changed from {$oldStatus} to {$delivery->status}.",
                    'reference_type' => 'checkout',
                    'is_read' => false,
                ]);

                event(new NotificationCreated($notif));

                $this->info('Notification created: ' . $notif->notification_id);
                $this->line(json_encode([
                    'notification_id' => $notif->notification_id,
                    'user_id' => $notif->user_id,
                    'type' => $notif->type,
                    'title' => $notif->title,
                    'message' => $notif->message,
                    'is_read' => (bool) $notif->is_read,
                ], JSON_PRETTY_PRINT));
            } else {
                $this->warn('No checkout/user associated with delivery; no notification created.');
            }

            DB::commit();
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
