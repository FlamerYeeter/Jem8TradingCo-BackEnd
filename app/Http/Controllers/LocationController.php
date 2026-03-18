<?php

namespace App\Http\Controllers;

use App\Events\LocationUpdated;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    /**
     * Store incoming location point.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'trip_id' => 'required|string',
            'user_id' => 'nullable|integer',
            'lat' => 'required|numeric|min:-90|max:90',
            'lng' => 'required|numeric|min:-180|max:180',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'bearing' => 'nullable|numeric',
        ]);

        try {
            $location = Location::create($data);

            // Broadcast immediately
            event(new LocationUpdated($location));

            return response()->json(['status' => 'ok', 'location' => $location], 201);
        } catch (\Exception $e) {
            Log::error('Location store error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unable to store location'], 500);
        }
    }

    /**
     * Return recent points for a trip.
     */
    public function recent(Request $request, $trip_id)
    {
        $limit = (int) $request->query('limit', 100);
        $points = Location::where('trip_id', $trip_id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return response()->json(['trip_id' => $trip_id, 'points' => $points]);
    }

    /**
     * Public store endpoint for driver/browser to post locations without auth (demo use).
     */
    public function storePublic(Request $request)
    {
        $data = $request->validate([
            'trip_id' => 'required|string',
            'user_id' => 'nullable|integer',
            'lat' => 'required|numeric|min:-90|max:90',
            'lng' => 'required|numeric|min:-180|max:180',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'bearing' => 'nullable|numeric',
        ]);

        try {
            $location = Location::create($data);
            event(new LocationUpdated($location));
            return response()->json(['status' => 'ok', 'location' => $location], 201);
        } catch (\Exception $e) {
            Log::error('Location public store error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Unable to store location'], 500);
        }
    }
}
