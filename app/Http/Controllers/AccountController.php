<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\VerificationCodeMail;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
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
    // REGISTER ✅ logged
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

        $code = rand(100000, 999999);
        $account->update([
            'email_verification_code'       => $code,
            'email_verification_expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($account->email)->send(new VerificationCodeMail($code));

        // ✅ Log: new account registered
        ActivityLog::log($account, 'Registered an account', 'account', [
            'description'     => $account->first_name . ' ' . $account->last_name . ' created a new account',
            'reference_table' => 'accounts',
            'reference_id'    => $account->id,
        ]);
                DB::table('notifications')->insert([
                    'user_id'    => null,
                    'type'       => 'user',
                    'title'      => 'New Account Registered',
                    'message'    => "{$account->first_name} {$account->last_name} just signed up.",
                    'is_read'    => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Cache::forget('dashboard.notifications');
        return response()->json([
            'message' => 'Account created. Please verify your email.',
            'data'    => $account
        ], 201);
    }

    // ==============================
    // LOGIN ✅ logged
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

        // ✅ Log: user logged in
        ActivityLog::log($account, 'Logged in', 'account', [
            'description'     => $account->first_name . ' ' . $account->last_name . ' logged in',
            'reference_table' => 'accounts',
            'reference_id'    => $account->id,
        ]);

        $cookie = cookie('jem8_token', $token, 60 * 24 * 30, '/', null, true, true, false, 'None');

        return response()->json([
            'status'  => 'success',
            'message' => 'Login successful',
            'token'   => $token,
        ])->withCookie($cookie);
    }

    // ==============================
    // LOGOUT ✅ logged
    // ==============================
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            // ✅ Log BEFORE logging out
            ActivityLog::log($user, 'Logged out', 'account', [
                'description'     => $user->first_name . ' ' . $user->last_name . ' logged out',
                'reference_table' => 'accounts',
                'reference_id'    => $user->id,
            ]);

            if ($request->user()->currentAccessToken()) {
                $request->user()->currentAccessToken()->delete();
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Logged out successfully',
        ])->withCookie(
            cookie('jem8_token', '', -1, '/', null, true, true, false, 'None')
        );
    }

    // ==============================
    // VERIFY EMAIL — no log needed
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
            'email_verified_at'             => now(),
            'email_verification_code'       => null,
            'email_verification_expires_at' => null,
        ]);

        return response()->json(['message' => 'Email verified successfully']);
    }

    // ==============================
    // SHOW ACCOUNT — no log needed
    // ==============================
    public function show($id)
    {
        try {
            $account = Account::findOrFail($id);
            return response()->json(['status' => 'success', 'data' => $account], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Account not found'], 404);
        }
    }

    // ==============================
    // FORGOT PASSWORD — no log needed
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
            'password_reset_code'       => $code,
            'password_reset_expires_at' => now()->addMinutes(15),
        ]);

        Mail::to($account->email)->send(new VerificationCodeMail($code));

        return response()->json(['message' => 'Password reset code sent']);
    }

    // ==============================
    // RESET PASSWORD — no log needed
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
            'password'                  => Hash::make($request->password),
            'password_reset_code'       => null,
            'password_reset_expires_at' => null,
        ]);

        return response()->json(['message' => 'Password reset successful']);
    }

    // ==============================
    // VIEW AUTHENTICATED USER — no log needed
    // ==============================
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['status' => 'failed', 'message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'                => $user->id,
                'google_id'         => $user->google_id ?? null,
                'first_name'        => $user->first_name,
                'last_name'         => $user->last_name,
                'phone_number'      => $user->phone_number,
                'company_name'      => $user->company_name ?? null,
                'position'          => $user->position ?? null,
                'business_type'     => $user->business_type ?? null,
                'email'             => $user->email,
                'role'              => $user->role,
                'profile_image'     => $user->profile_image
                    ? (str_starts_with($user->profile_image, 'http')
                        ? $user->profile_image
                        : asset('storage/' . $user->profile_image))
                    : null,
                'tin_number'        => $user->tin_number ?? null,
                'email_verified_at' => $user->email_verified_at,
                'created_at'        => $user->created_at,
            ]
        ]);
    }

    // ==============================
    // UPDATE PROFILE — no log needed
    // ==============================
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name'   => 'sometimes|string|max:255',
            'last_name'    => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|unique:accounts,phone_number,' . $user->id,
            'email'        => 'sometimes|email|unique:accounts,email,' . $user->id,
            'company_name' => 'sometimes|nullable|string|max:255',
            'position'     => 'sometimes|nullable|string|max:255',
            'business_type'=> 'sometimes|nullable|string|max:255',
            'tin_number' => 'sometimes|nullable|string|min:9|max:12|regex:/^\d{9,12}$/',
        ]);

        $user->update($validated);

        return response()->json(['message' => 'Profile updated successfully', 'data' => $user]);
    }

    // ==============================
    // UPDATE PROFILE IMAGE — product-style upload
    // ==============================
    public function updateProfileImage(Request $request)
    {
        try {
            $request->validate([
                'profile_image' => 'required|image|mimes:jpg,jpeg,png|max:2048'
            ]);

            $user = $request->user();

            Log::info('=== UPDATE PROFILE IMAGE ===');
            Log::info('User ID: ' . $user->id);
            Log::info('Has file?', ['has_file' => $request->hasFile('profile_image')]);

            if (!$request->hasFile('profile_image')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No image file found in request',
                ], 400);
            }

            $image = $request->file('profile_image');

            if (!$image->isValid()) {
                Log::error('Invalid profile image file', ['error' => $image->getErrorMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Uploaded file is invalid: ' . $image->getErrorMessage(),
                ], 400);
            }

            // Create upload directory if it doesn't exist
            $uploadPath = public_path('storage/profile_images');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
                Log::info('Created directory: ' . $uploadPath);
            }

            // Delete old profile image if it exists and is not an external URL
            if ($user->profile_image && !str_starts_with($user->profile_image, 'http')) {
                $oldFilePath = public_path('storage/' . $user->profile_image);
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                    Log::info('Deleted old profile image: ' . $oldFilePath);
                }
            }

            // Generate unique filename and move file
            $extension = $image->getClientOriginalExtension();
            $filename  = time() . '_' . uniqid() . '.' . $extension;

            Log::info('Saving profile image:', [
                'filename' => $filename,
                'size'     => $image->getSize(),
                'mime'     => $image->getMimeType(),
            ]);

            $image->move($uploadPath, $filename);

            // Save relative path (matches product pattern: 'profile_images/filename.jpg')
            $relativePath = 'profile_images/' . $filename;
            $user->update(['profile_image' => $relativePath]);

            Log::info('Profile image saved successfully', ['path' => $relativePath]);

            return response()->json([
                'success'           => true,
                'message'           => 'Profile image updated successfully',
                'profile_image_url' => asset('storage/' . $relativePath),
            ]);

        } catch (\Exception $e) {
            Log::error('Update profile image failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile image',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ==============================
    // DELETE OWN ACCOUNT ✅ logged
    // ==============================
    public function destroy(Request $request)
    {
        $user = $request->user();
        $name = $user->first_name . ' ' . $user->last_name;
        $id   = $user->id;

        // ✅ Log BEFORE deleting
        ActivityLog::log($user, 'Deleted account', 'account', [
            'description'     => $name . ' deleted their account',
            'reference_table' => 'accounts',
            'reference_id'    => $id,
        ]);

        // Delete profile image file if it exists and is not an external URL
        if ($user->profile_image && !str_starts_with($user->profile_image, 'http')) {
            $filePath = public_path('storage/' . $user->profile_image);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }

    // ==============================
    // ADMIN UPDATE ACCOUNT — no log needed
    // ==============================
    public function update(Request $request, $id)
    {
        $account = Account::findOrFail($id);

        $account->update($request->only([
            'first_name',
            'last_name',
            'email',
            'phone_number',
            'role',
        ]));

        return response()->json(['data' => $account]);
    }

    // ==============================
    // ADMIN DELETE ACCOUNT ✅ logged
    // ==============================
    public function adminDestroy($id)
    {
        $account = Account::findOrFail($id);
        $name    = $account->first_name . ' ' . $account->last_name;

        // ✅ Log: admin deleted an account
        ActivityLog::log(Auth::user(), 'Deleted an account', 'account', [
            'description'     => Auth::user()->first_name . ' deleted account of: ' . $name,
            'reference_table' => 'accounts',
            'reference_id'    => $id,
        ]);

        $account->delete();

        return response()->json(['message' => 'Account deleted successfully.']);
    }
}
