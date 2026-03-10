<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\VerificationCodeMail;

class AccountController extends Controller
{
    // ==============================
    // GET ALL ACCOUNTS (ADMIN USE)
    // ==============================
    public function index()
    {
        return response()->json(Account::all());
    }

    // ==============================
    // REGISTER
    // ==============================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'phone_number' => 'required|string|unique:accounts,phone_number',
            'email'        => 'required|email|unique:accounts,email',
            'password'     => 'required|string|min:6',
        ]);

        $account = Account::create([
            'first_name'   => $validated['first_name'],
            'last_name'    => $validated['last_name'],
            'phone_number' => $validated['phone_number'],
            'email'        => $validated['email'],
            'password'     => Hash::make($validated['password']),
        ]);

        // Generate 6-digit verification code
        $code = rand(100000, 999999);

        $account->update([
            'email_verification_code' => $code,
            'email_verification_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($account->email)->send(new VerificationCodeMail($code));

        return response()->json([
            'message' => 'Account created. Please verify your email.',
            'data' => $account
        ], 201);
    }

    // ==============================
    // LOGIN
    // ==============================
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string'
        ]);

        $account = Account::where('email', $request->email)->first();

        if (!$account || !Hash::check($request->password, $account->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$account->email_verified_at) {
            return response()->json(['message' => 'Please verify your email first'], 403);
        }

        $token = $account->createToken('jem8_token')->plainTextToken;

        // Set cookie properly
        $cookie = cookie(
            'jem8_token', 
            $token, 
            60*24*30,   // 30 days
            '/',        // path
            null,       // domain null for localhost
            true,      // secure false for local dev
            true,       // httpOnly
            false,      // raw
            'None'       // sameSite safe for local dev
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $token
        ])->withCookie($cookie);
    }
        // ==============================
    // LOGOUT
    // ==============================
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {

            // Delete token only if it exists
            if ($request->user()->currentAccessToken()) {
                $request->user()->currentAccessToken()->delete();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ])->withCookie(
            cookie(
                'jem8_token',
                '',
                -1,           // Expire immediately
                '/',          // MUST match path
                null,         // MUST match domain
                true,         // MUST match secure
                true,         // httpOnly
                false,
                'None'        // MUST match SameSite
            )
        );
    }

    // ==============================
    // VERIFY EMAIL
    // ==============================
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|digits:6',
        ]);

        $account = Account::where('email', $request->email)->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        if ($account->email_verified_at) {
            return response()->json(['message' => 'Email already verified']);
        }

        if (
            $account->email_verification_code != $request->code ||
            $account->email_verification_expires_at < now()
        ) {
            return response()->json(['message' => 'Invalid or expired code'], 400);
        }

        $account->update([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
        ]);

        return response()->json(['message' => 'Email verified successfully']);
    }

    // ==============================
    // FORGOT PASSWORD
    // ==============================
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $account = Account::where('email', $request->email)->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $code = rand(100000, 999999);

        $account->update([
            'password_reset_code' => $code,
            'password_reset_expires_at' => now()->addMinutes(15),
        ]);

        Mail::to($account->email)->send(new VerificationCodeMail($code));

        return response()->json(['message' => 'Password reset code sent']);
    }

    // ==============================
    // RESET PASSWORD
    // ==============================
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'code'     => 'required|digits:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $account = Account::where('email', $request->email)->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        if (
            $account->password_reset_code != $request->code ||
            $account->password_reset_expires_at < now()
        ) {
            return response()->json(['message' => 'Invalid or expired code'], 400);
        }

        $account->update([
            'password' => Hash::make($request->password),
            'password_reset_code' => null,
            'password_reset_expires_at' => null,
        ]);

        return response()->json(['message' => 'Password reset successful']);
    }

    // ==============================
    // VIEW AUTHENTICATED USER
    // ==============================
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthenticated'
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id'            => $user->id,
                'first_name'    => $user->first_name,
                'last_name'     => $user->last_name,
                'phone_number'  => $user->phone_number,
                'email'         => $user->email,
                'profile_image' => $user->profile_image 
                    ? asset('storage/' . $user->profile_image)
                    : null,
                'email_verified_at' => $user->email_verified_at,
                'created_at'    => $user->created_at,
            ]
        ]);
    }

    // ==============================
    // UPDATE PROFILE DETAILS
    // ==============================
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name'   => 'sometimes|string|max:255',
            'last_name'    => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|unique:accounts,phone_number,' . $user->id,
            'email'        => 'sometimes|email|unique:accounts,email,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }

    // ==============================
    // UPDATE PROFILE IMAGE
    // ==============================
    public function updateProfileImage(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $user = $request->user();

        // Delete old image
        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }

        $path = $request->file('profile_image')
            ->store('profile_images', 'public');

        $user->update([
            'profile_image' => $path
        ]);

        return response()->json([
            'message' => 'Profile image updated successfully',
            'profile_image_url' => asset('storage/' . $path)
        ]);
    }

    // ==============================
    // DELETE ACCOUNT
    // ==============================
    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }
}