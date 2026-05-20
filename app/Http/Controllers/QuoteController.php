<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Contact;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\ProductRequest;
use Illuminate\Support\Facades\Log;

class QuoteController extends Controller
{
    // Public: accept quote requests as JSON or FormData
    public function store(StoreContactRequest $request)
    {
        try {
            // Handle file attachment upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('contact_attachments', 'public');
            }

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
                $company = $request->input('company');
            }

            // Build message body: always capture items, company, order reference, and notes as structured JSON
            $notes = $request->input('message') ?? $request->input('notes') ?? '';
            
            $payload = [];
            if ($request->has('items')) {
                $items = $request->input('items');
                if (is_string($items)) {
                    $decoded = json_decode($items, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $items = $decoded;
                    }
                }
                $payload['items'] = $items;
            }
            if ($request->has('order_reference')) {
                $payload['order_reference'] = $request->input('order_reference');
            }
            if ($company) { 
                $payload['company'] = $company; 
            }
            if (!empty($notes)) { 
                $payload['notes'] = $notes; 
            }

            $messageBody = json_encode($payload);

            $contact = Contact::create([
                'first_name'      => $first,
                'last_name'       => $last,
                'phone_number'    => $phone ?? '',
                'email'           => $email,
                'message'         => $messageBody,
                'message_type'    => 'inquiry',
                'attachment_path' => $attachmentPath,
                'status'          => 'pending',
            ]);

            // Add notification for the admin
            DB::table('notifications')->insert([
                'user_id'    => null,
                'type'       => 'contact',
                'title'      => 'New Quote Request',
                'message'    => "Quote Request from {$first} {$last}",
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // If items were provided, also create ProductRequest rows so Sales can track them
            $createdRequests = [];
            try {
                if (!empty($items) && is_array($items)) {
                    foreach ($items as $it) {
                        $pid = $it['product_id'] ?? null;
                        $qty = $it['quantity'] ?? null;
                        $productName = null;
                        if ($pid) {
                            $p = \App\Models\Product::where('product_id', $pid)->first();
                            $productName = $p->product_name ?? null;
                        }

                        $pr = ProductRequest::create([
                            'user_id' => optional($request->user())->id,
                            'product_id' => $pid,
                            'product_name' => $productName,
                            'description' => $messageBody ?: ('Quote request; qty: ' . ($qty ?? '1')),
                            'image_path' => null,
                            'status' => 'pending',
                        ]);
                        $createdRequests[] = $pr;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed creating ProductRequest from Quote', ['error' => $e->getMessage(), 'payload' => $request->all()]);
            }

            $resp = ['data' => $contact];
            if (!empty($createdRequests)) { $resp['product_requests'] = $createdRequests; }

            return response()->json($resp, 201);
        } catch (ValidationException $ve) {
            return response()->json(['errors' => $ve->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => [$e->getMessage()]]], 500);
        }
    }
}

