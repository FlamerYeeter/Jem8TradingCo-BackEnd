<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Contact;
use Illuminate\Validation\ValidationException;

class QuoteController extends Controller
{
    // Public: accept quote requests as JSON
    public function store(StoreContactRequest $request)
    {
        try {
            // Normalize name/email/phone
            if ($request->has('contact')) {
                $c = $request->input('contact');
                $fullName = trim($c['name'] ?? '');
                if (!empty($fullName)) {
                    $parts = preg_split('/\s+/', $fullName, 2);
                    $first = $parts[0] ?? '';
                    $last  = $parts[1] ?? '';
                } else {
                    $first = $request->input('first_name', '');
                    $last  = $request->input('last_name', '');
                }
                $email = $c['email'] ?? $request->input('email');
                $phone = $c['phone'] ?? $request->input('phone_number') ?? '';
                $company = $c['company'] ?? null;
            } else {
                $first = $request->input('first_name');
                $last  = $request->input('last_name');
                $email = $request->input('email');
                $phone = $request->input('phone_number') ?? '';
                $company = null;
            }

            // Build message body: prefer provided message, otherwise include items/order_reference
            $messageBody = $request->input('message');
            if (empty($messageBody)) {
                $payload = [];
                if ($request->has('items')) {
                    $payload['items'] = $request->input('items');
                }
                if ($request->has('order_reference')) {
                    $payload['order_reference'] = $request->input('order_reference');
                }
                if ($company) { $payload['company'] = $company; }
                $messageBody = json_encode($payload);
            }

            $contact = Contact::create([
                'first_name'      => $first,
                'last_name'       => $last,
                'phone_number'    => $phone ?? '',
                'email'           => $email,
                'message'         => $messageBody,
                'message_type'    => 'inquiry',
                'status'          => 'pending',
            ]);

            return response()->json(['data' => $contact], 201);
        } catch (ValidationException $ve) {
            return response()->json(['errors' => $ve->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => [$e->getMessage()]]], 500);
        }
    }
}
