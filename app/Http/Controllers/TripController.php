<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TripController extends Controller
{
    // Create or update a trip (public, demo)
    public function store(Request $request)
    {
        $data = $request->validate([
            'trip_id' => 'required|string',
            'start_lat' => 'nullable|numeric',
            'start_lng' => 'nullable|numeric',
            'dest_lat' => 'nullable|numeric',
            'dest_lng' => 'nullable|numeric',
            'dest_address' => 'nullable|string',
        ]);

        try {
            $trip = Trip::updateOrCreate(
                ['trip_id' => $data['trip_id']],
                $data
            );
            return response()->json(['status' => 'ok', 'trip' => $trip], 201);
        } catch (\Exception $e) {
            Log::error('Trip store error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unable to store trip'], 500);
        }
    }

    // Public get
    public function show($trip_id)
    {
        $trip = Trip::where('trip_id', $trip_id)->first();
        if (!$trip) {
            return response()->json(['status' => 'not_found'], 404);
        }
        return response()->json(['status' => 'ok', 'trip' => $trip]);
    }
}
