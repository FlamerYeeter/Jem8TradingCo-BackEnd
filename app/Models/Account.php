<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $phone_number
 * @property string $email
 * @property string $password
 * @property string|null $email_verification_code
 * @property string|null $email_verification_expires_at
 * @property string|null $password_reset_code
 * @property string|null $password_reset_expires_at
 * @property string|null $email_verified_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereEmailVerificationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereEmailVerificationExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account wherePasswordResetCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account wherePasswordResetExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Account extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'password',
        'email_verification_code',
        'email_verification_expires_at',
        'password_reset_code',
        'password_reset_expires_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}