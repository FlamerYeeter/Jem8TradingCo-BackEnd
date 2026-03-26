<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactReply;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    // ✅ POST - Create contact message (public)
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

            // ✅ Log: only if logged in
            if (Auth::check()) {
                ActivityLog::log(Auth::user(), 'Sent a contact message', 'contacts', [
                    'description'     => Auth::user()->first_name . ' sent a contact message',
                    'reference_table' => 'contacts',
                    'reference_id'    => $contact->message_id,
                ]);
            }

            return response()->json(['status' => 'success', 'data' => $contact], 201);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ GET - All contacts (admin)
    public function index()
    {
        try {
            $contacts = Contact::orderBy('created_at', 'desc')->get();

            // ✅ Log: admin viewed contacts list
            ActivityLog::log(Auth::user(), 'Viewed contacts list', 'contacts', [
                'description'     => Auth::user()->first_name . ' viewed the contacts list',
                'reference_table' => 'contacts',
            ]);

            return response()->json(['status' => 'success', 'data' => $contacts], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ GET - Single contact (admin)
    public function show($id)
    {
        try {
            $contact = Contact::findOrFail($id);

            // ✅ Log: admin viewed a contact
            ActivityLog::log(Auth::user(), 'Viewed a contact message', 'contacts', [
                'description'     => Auth::user()->first_name . ' viewed contact message from: ' . $contact->first_name . ' ' . $contact->last_name,
                'reference_table' => 'contacts',
                'reference_id'    => $id,
            ]);

            return response()->json(['status' => 'success', 'data' => $contact], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // PATCH - Update status (admin) — no log needed
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,read,replied',
            ]);

            $contact = Contact::findOrFail($id);
            $contact->update(['status' => $request->input('status')]);

            return response()->json(['status' => 'success', 'data' => $contact], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ DELETE - Delete contact (admin)
    public function destroy($id)
    {
        try {
            $contact = Contact::findOrFail($id);
            $name    = $contact->first_name . ' ' . $contact->last_name;
            $contact->delete();

            // ✅ Log: admin deleted a contact
            ActivityLog::log(Auth::user(), 'Deleted a contact message', 'contacts', [
                'description'     => Auth::user()->first_name . ' deleted contact message from: ' . $name,
                'reference_table' => 'contacts',
                'reference_id'    => $id,
            ]);

            return response()->json(['status' => 'success', 'message' => 'Contact message deleted successfully.'], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // POST - Reply to contact (admin) — no log needed
    public function reply(Request $request, $id)
    {
        try {
            $request->validate([
                'reply_message' => 'required|string|max:2000',
            ]);

            $contact = Contact::findOrFail($id);

            Mail::to($contact->email)->send(
                new ContactReply(
                    $contact->first_name . ' ' . $contact->last_name,
                    $request->input('reply_message')
                )
            );

            $contact->update(['status' => 'replied']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Reply sent successfully to ' . $contact->email,
                'data'    => $contact,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}