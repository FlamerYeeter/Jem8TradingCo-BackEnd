<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $primaryKey = 'activity_id';

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'category',
        'product_name',
        'product_unique_code',
        'mode_of_payment',
        'amount',
        'meta',
        'reference_table',
        'reference_id',
        'description',
        'logged_at',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'logged_at'  => 'datetime',
        'created_at' => 'datetime',
        'meta'       => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function account()
    {
        return $this->belongsTo(Account::class, 'user_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('user_name', 'like', "%{$keyword}%")
              ->orWhere('action', 'like', "%{$keyword}%")
              ->orWhere('product_name', 'like', "%{$keyword}%")
              ->orWhere('product_unique_code', 'like', "%{$keyword}%")
              ->orWhere('description', 'like', "%{$keyword}%");
        });
    }

    // ── Core Log Method ───────────────────────────────────────────────────────

    /**
     * Create an activity log entry.
     *
     * @param  mixed   $user     Account model instance
     * @param  string  $action   Short action label (e.g. "purchased", "updated stock")
     * @param  string  $category One of: orders, stock, account, blogs, payments, backups, other
     * @param  array   $extra    Optional extra fields (product_name, amount, mode_of_payment, etc.)
     */
    public static function log($user, string $action, string $category = 'other', array $extra = []): self
    {
        // Resolve display name from Account (first_name + last_name) or fall back to email
        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        if ($name === '') {
            $name = $user->email ?? 'Unknown';
        }

        // Always stamp logged_at in Asia/Manila timezone
        $now = Carbon::now('Asia/Manila');

        $data = array_merge([
            'user_id'   => $user->id,
            'user_name' => $name,
            'action'    => $action,
            'category'  => $category,
            'logged_at' => $now,
        ], $extra);

        // Auto-generate a human-readable description if one was not supplied
        if (empty($data['description'])) {
            $data['description'] = self::buildDescription($name, $action, $category, $data);
        }

        // Strip keys not present in the DB schema to prevent SQL errors
        try {
            $columns = Schema::getColumnListing((new self())->getTable());
            $data = array_filter($data, fn($key) => in_array($key, $columns, true), ARRAY_FILTER_USE_KEY);
        } catch (\Exception) {
            // Schema manager unavailable – proceed with raw data
        }

        return self::create($data);
    }

    // ── Auto-purge Helpers ────────────────────────────────────────────────────

    /**
     * Delete logs older than $days days (default: 3).
     * Call from a scheduled command or Laravel scheduler.
     */
    public static function purgeOld(int $days = 3): int
    {
        $cutoff = Carbon::now('Asia/Manila')->subDays($days);

        return self::where('logged_at', '<', $cutoff)->delete();
    }

    // ── Description Builder ───────────────────────────────────────────────────

    /**
     * Generate a clear, human-readable sentence describing the logged action.
     */
    protected static function buildDescription(
        string $userName,
        string $action,
        string $category,
        array  $data
    ): string {
        $product = $data['product_name']     ?? null;
        $code    = $data['product_unique_code'] ?? null;
        $amount  = isset($data['amount']) && $data['amount'] !== null
            ? '₱' . number_format((float) $data['amount'], 2)
            : null;
        $payment = $data['mode_of_payment']  ?? null;
        $ref     = ($data['reference_table'] ?? null) && ($data['reference_id'] ?? null)
            ? strtoupper($data['reference_table']) . ' #' . $data['reference_id']
            : null;

        // Build contextual parts
        $productPart  = $product ? " '{$product}'" : ($code ? " [{$code}]" : '');
        $amountPart   = $amount  ? " for {$amount}"    : '';
        $paymentPart  = $payment ? " via {$payment}"   : '';
        $refPart      = $ref     ? " (ref: {$ref})"    : '';

        // Category-aware templates
        switch ($category) {
            case 'orders':
                return "{$userName} {$action}{$productPart}{$amountPart}{$paymentPart}{$refPart}.";

            case 'payments':
                return "{$userName} {$action}{$amountPart}{$paymentPart}{$productPart}{$refPart}.";

            case 'stock':
                return "{$userName} {$action}{$productPart}{$refPart}.";

            case 'account':
                return "{$userName} {$action} their account{$refPart}.";

            case 'blogs':
                return "{$userName} {$action}{$productPart}{$refPart}.";

            case 'backups':
                return "{$userName} {$action} a system backup{$refPart}.";

            default:
                return "{$userName} {$action}{$productPart}{$amountPart}{$refPart}.";
        }
    }
}