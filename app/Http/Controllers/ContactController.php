<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use App\Mail\ContactReply;


class ContactController extends Controller
{
    // Create a new contact message (public)
    public function store(Request $request)
    {
        try {
            $request->validate([
                'first_name'   => 'required|string|max:255',
                'last_name'    => 'required|string|max:255',
                'phone_number' => 'nullable|string|max:20',
                'email'        => 'required|email|max:255',
                'message'      => 'required|string|max:2000',
            ]);

            $contact = Contact::create([
                'first_name'   => $request->input('first_name'),
                'last_name'    => $request->input('last_name'),
                'phone_number' => $request->input('phone_number'),
                'email'        => $request->input('email'),
                'message'      => $request->input('message'),
                'status'       => 'pending',
            ]);

            return response()->json([
                'status' => 'success',
                'data'   => $contact,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Get all contact messages (admin)
    public function index()
    {
        try {
            $contacts = Contact::orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'data'   => $contacts,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Get single contact message (admin)
    public function show($id)
    {
        try {
            $contact = Contact::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $contact,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Update status of contact message (admin)
    // status: pending | read | replied
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,read,replied',
            ]);

            $contact = Contact::findOrFail($id);
            $contact->update(['status' => $request->input('status')]);

            return response()->json([
                'status' => 'success',
                'data'   => $contact,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Delete contact message (admin)
    public function destroy($id)
    {
        try {
            $contact = Contact::findOrFail($id);
            $contact->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Contact message deleted successfully.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function reply(Request $request, $id)
    {
        try {
            $request->validate([
                'reply_message' => 'required|string|max:2000',
            ]);

            $contact = Contact::findOrFail($id);

            // Send email
            Mail::to($contact->email)->send(
                new ContactReply(
                    $contact->first_name . ' ' . $contact->last_name,
                    $request->input('reply_message')
                )
            );

            // Auto update status to replied
            $contact->update(['status' => 'replied']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Reply sent successfully to ' . $contact->email,
                'data'    => $contact,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
