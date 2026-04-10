<?php
// app/Http/Controllers/NewsletterController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please enter a valid email address.'
            ], 422);
        }

        $response = Http::withHeaders([
            'api-key'      => env('BREVO_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.brevo.com/v3/contacts', [
            'email'         => $request->email,
            'listIds'       => [intval(env('BREVO_LIST_ID'))],
            'updateEnabled' => true,
        ]);

        if ($response->status() === 201) {
            return response()->json(['message' => "You're subscribed! Thank you 🎉"], 201);
        }

        if ($response->status() === 204) {
            return response()->json(['message' => "You're already subscribed!"], 200);
        }

        return response()->json([
            'message' => $response->json('message') ?? 'Something went wrong. Please try again.'
        ], 500);
    }
}