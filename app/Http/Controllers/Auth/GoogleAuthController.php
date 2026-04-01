<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return response()->json([
            'url' => $url
        ]);
    }

    public function callback()
{
    try {
        $googleUser = Socialite::driver('google')->stateless()->user();
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Invalid Google token',
            'error'   => $e->getMessage(),
        ], 401);
    }

    $isNewUser = false;

    $account = Account::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

    if (!$account) {
        $isNewUser = true;
        $account = Account::create([
             'first_name'        => $googleUser->user['given_name'] ?? '',
        'last_name'         => $googleUser->user['family_name'] ?? '',
        'email'             => $googleUser->getEmail(),
        'google_id'         => $googleUser->getId(),
        'profile_image'     => $googleUser->getAvatar(),
        'email_verified_at' => now(),
        'password'          => null,
        ]);
    } else {
        $account->update([
           'google_id'     => $account->google_id ?? $googleUser->getId(),
        'profile_image' => $googleUser->getAvatar(),
        ]);
    }

    $token = $account->createToken('google-token')->plainTextToken;

    return response()->json([
        'token'       => $token,
        'user'        => $account,
        'is_new_user' => $isNewUser,
    ]);
}
}
