<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'reference_table',
        'reference_id',
        'description',
        'logged_at',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'logged_at'  => 'datetime',
        'created_at' => 'datetime',
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

        return self::create(array_merge([
            'user_id'   => $user->id,
            'user_name' => $name,
            'action'    => $action,
            'category'  => $category,
            'logged_at' => now(),
        ], $extra));
    }
}