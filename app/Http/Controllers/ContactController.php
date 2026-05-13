<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactReply;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
class ContactController extends Controller
{
    // ✅ POST - Create contact message (public)
    public function store(StoreContactRequest $request)
    {
        try {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('contact_attachments', 'public');
            }

            // if frontend used nested `contact` object, extract fields
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
            } else {
                $first = $request->input('first_name');
                $last  = $request->input('last_name');
                $email = $request->input('email');
                $phone = $request->input('phone_number') ?? '';
            }

            $contact = Contact::create([
                'first_name'      => $first,
                'last_name'       => $last,
                'phone_number'    => $phone,
                'email'           => $email,
                'message'         => $request->input('message'),
                'message_type'    => $request->input('message_type') ?? 'inquiry',
                'attachment_path' => $attachmentPath,
                'status'          => 'pending',
            ]);
                 DB::table('notifications')->insert([
                'user_id'    => null,
                'type'       => 'contact',
                'title'      => 'New Contact Message',
                'message'    => "{$contact->first_name} {$contact->last_name}: " . Str::limit($contact->message, 60),
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Cache::forget('dashboard.notifications');
            // ✅ Log: only if logged in
            if (Auth::check()) {
                ActivityLog::log(Auth::user(), 'Sent a contact message', 'contacts', [
                    'description'     => Auth::user()->first_name . ' sent a contact message',
                    'reference_table' => 'contacts',
                    'reference_id'    => $contact->message_id,
                ]);
            }

            return response()->json(['data' => $contact], 201);

        } catch (ValidationException $ve) {
            return response()->json(['errors' => $ve->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => [$e->getMessage()]]], 500);
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
            if (empty($id) || !is_numeric($id)) {
                return response()->json(['status' => 'error', 'message' => 'Invalid contact id'], 400);
            }
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
            if (empty($id) || !is_numeric($id)) {
                return response()->json(['status' => 'error', 'message' => 'Invalid contact id'], 400);
            }
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
            if (empty($id) || !is_numeric($id)) {
                return response()->json(['status' => 'error', 'message' => 'Invalid contact id'], 400);
            }
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
    public function reply(Request $request, $contactId)
    {
        try {
            if (empty($contactId) || !is_numeric($contactId)) {
                return response()->json(['status' => 'error', 'message' => 'Invalid contact id'], 400);
            }
            $request->validate([
                'reply_message' => 'required|string|max:2000',
            ]);

            $contact = Contact::findOrFail($contactId);

            $replyText = $request->input('reply_message');

            // Queue the reply email
            try {
                Mail::to($contact->email)->queue(
                    new ContactReply(
                        $contact->first_name . ' ' . $contact->last_name,
                        $replyText
                    )
                );
            } catch (\Exception $e) {
                // If queue/send fails, log but continue to save reply state
                \Illuminate\Support\Facades\Log::error('Failed to queue contact reply email', [
                    'error' => $e->getMessage(),
                    'contact_id' => $contact->getKey(),
                ]);
            }

            // Save reply details and mark replied
            $contact->update([
                'reply_message' => $replyText,
                'replied_by'    => optional(Auth::user())->id,
                'replied_at'    => now(),
                'status'        => 'replied',
            ]);

            // Log admin reply
            ActivityLog::log(Auth::user(), 'Replied to contact message', 'contacts', [
                'description'     => Auth::user()->first_name . ' replied to contact message from: ' . $contact->first_name . ' ' . $contact->last_name,
                'reference_table' => 'contacts',
                'reference_id'    => $contact->getKey(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Reply queued and saved for ' . $contact->email,
                'data'    => $contact->fresh(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
