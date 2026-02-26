<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function index()
    {
        return response()->json(Account::all());
    }

    public function login(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        // Find account
        $account = Account::where('email', $request->email)->first();

        if (!$account || !Hash::check($request->password, $account->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Create token
        $token = $account->createToken('auth_token')->plainTextToken;

        // Set cookie with token, HTTP-only
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
            'message' => 'Login successful',
            'account' => $account
        ])->withCookie($cookie);
    }
    public function logout(Request $request)
    {
        // Delete the cookie
        $cookie = cookie()->forget('jwt_token');

        // Optionally delete token from DB
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully'
        ])->withCookie($cookie);
    }
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:accounts,phone_number',
            'email' => 'required|string|email|unique:accounts,email',
            'password' => 'required|string|min:6',
        ]);

        // Create account with hashed password
        $account = Account::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Account created successfully',
            'data' => $account
        ], 201);
    }

    public function show(string $id)
    {
        $account = Account::findOrFail($id);
        return response()->json($account);
    }

    public function update(Request $request, string $id)
    {
        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|unique:accounts,email,' . $id,
            'password' => 'sometimes|string|min:6',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $account->update($validated);

        return response()->json([
            'message' => 'Account updated successfully',
            'data' => $account
        ]);
    }

    

    public function destroy(string $id)
    {
        $account = Account::findOrFail($id);
        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }
}