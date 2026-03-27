<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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

    public function account()
    {
        return $this->belongsTo(Account::class, 'user_id');
    }

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

    public static function log($user, string $action, string $category = 'other', array $extra = []): self
    {
        // accounts table uses first_name + last_name
        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        if (!$name || $name === '') {
            $name = $user->email ?? 'Unknown';
        }

        $data = array_merge([
            'user_id'   => $user->id,
            'user_name' => $name,
            'action'    => $action,
            'category'  => $category,
            'logged_at' => now(),
        ], $extra);

        // Filter to only columns that actually exist in the table to avoid SQL errors
        try {
            $columns = Schema::getColumnListing((new self())->getTable());
            $data = array_filter($data, function ($value, $key) use ($columns) {
                return in_array($key, $columns, true);
            }, ARRAY_FILTER_USE_BOTH);
        } catch (\Exception $e) {
            // If schema manager isn't available, fall back to original data
        }

        return self::create($data);
    }
}