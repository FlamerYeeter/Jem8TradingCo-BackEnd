<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAddressController extends Controller
{
    // ✅ GET - All addresses for authenticated user
    public function index()
    {
        $addresses = UserAddress::where('user_id', Auth::id())->get();

        // ✅ Log: user viewed their addresses
        ActivityLog::log(Auth::user(), 'Viewed addresses', 'account', [
            'description'     => Auth::user()->first_name . ' viewed their addresses',
            'reference_table' => 'user_addresses',
        ]);

        return response()->json(['status' => 200, 'data' => $addresses]);
    }

    // ✅ POST - Store new address
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'           => 'required|in:personal,company',
            'company_name'   => 'nullable|string|max:255',
            'company_role'   => 'nullable|string|max:255',
            'company_number' => 'nullable|string|max:50',
            'company_email'  => 'nullable|email|max:255',
            'street'         => 'required|string|max:255',
            'barangay'       => 'nullable|string|max:255',
            'city'           => 'required|string|max:255',
            'province'       => 'nullable|string|max:255',
            'postal_code'    => 'nullable|string|max:20',
            'country'        => 'nullable|string|max:255',
            'status'         => 'nullable|in:active,inactive',
        ]);

        $address = UserAddress::create([
            'user_id' => Auth::id(),
            ...$validated,
        ]);

        // ✅ Log: user added an address
        ActivityLog::log(Auth::user(), 'Added an address', 'account', [
            'description'     => Auth::user()->first_name . ' added a new address in ' . $request->city,
            'reference_table' => 'user_addresses',
            'reference_id'    => $address->id,
        ]);

        return response()->json(['status' => 200, 'data' => $address]);
    }

    // ✅ GET - Show single address
    public function show($id)
    {
        $address = UserAddress::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        return response()->json(['status' => 200, 'data' => $address]);
    }

    // UPDATE address — no log needed
    public function update(Request $request, $id)
    {
        $address = UserAddress::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'type'           => 'sometimes|in:personal,company',
            'company_name'   => 'nullable|string|max:255',
            'company_role'   => 'nullable|string|max:255',
            'company_number' => 'nullable|string|max:50',
            'company_email'  => 'nullable|email|max:255',
            'street'         => 'sometimes|string|max:255',
            'barangay'       => 'nullable|string|max:255',
            'city'           => 'sometimes|string|max:255',
            'province'       => 'nullable|string|max:255',
            'postal_code'    => 'nullable|string|max:20',
            'country'        => 'nullable|string|max:255',
            'status'         => 'nullable|in:active,inactive',
        ]);

        $address->update($validated);

        return response()->json(['status' => 200, 'data' => $address]);
    }

    // ✅ DELETE - Delete address
    public function destroy($id)
    {
        $address = UserAddress::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $address->delete();

        // ✅ Log: user deleted an address
        ActivityLog::log(Auth::user(), 'Deleted an address', 'account', [
            'description'     => Auth::user()->first_name . ' deleted an address',
            'reference_table' => 'user_addresses',
            'reference_id'    => $id,
        ]);

        return response()->json(['status' => 200, 'message' => 'Address deleted successfully']);
    }
}