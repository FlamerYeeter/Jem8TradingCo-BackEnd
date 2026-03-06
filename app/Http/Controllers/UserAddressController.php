<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAddressController extends Controller
{
    // List all addresses for authenticated user
    public function index()
    {
        $addresses = UserAddress::where('user_id', Auth::id())->get();

        return response()->json([
            'status' => 200,
            'data'   => $addresses,
        ]);
    }

    // Store new address
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name'   => 'nullable|string|max:255',
            'company_role'   => 'nullable|string|max:255',
            'company_number' => 'nullable|string|max:50',
            'company_email'  => 'nullable|email|max:255',
            'address'        => 'required|string',
            'status'         => 'nullable|in:active,inactive',
        ]);

        $address = UserAddress::create([
            'user_id'        => Auth::id(),
            ...$validated,
        ]);

        return response()->json([
            'status' => 200,
            'data'   => $address,
        ]);
    }

    // Show single address
    public function show($id)
    {
        $address = UserAddress::where('user_id', Auth::id())
                              ->where('user_address_id', $id)
                              ->firstOrFail();

        return response()->json([
            'status' => 200,
            'data'   => $address,
        ]);
    }

    // Update existing address
    public function update(Request $request, $id)
    {
        $address = UserAddress::where('user_id', Auth::id())
                              ->where('user_address_id', $id)
                              ->firstOrFail();

        $validated = $request->validate([
            'company_name'   => 'nullable|string|max:255',
            'company_role'   => 'nullable|string|max:255',
            'company_number' => 'nullable|string|max:50',
            'company_email'  => 'nullable|email|max:255',
            'address'        => 'required|string',
            'status'         => 'nullable|in:active,inactive',
        ]);

        $address->update($validated);

        return response()->json([
            'status' => 200,
            'data'   => $address,
        ]);
    }

    // Delete address
    public function destroy($id)
    {
        $address = UserAddress::where('user_id', Auth::id())
                              ->where('user_address_id', $id)
                              ->firstOrFail();

        $address->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Address deleted successfully',
        ]);
    }
}