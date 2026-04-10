<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Account;
use Carbon\Carbon;
use Exception;

class ActivityLogController extends Controller
{
    // Timezone used for all display / grouping
    private const TZ = 'Asia/Manila';

    // ── Read ──────────────────────────────────────────────────────────────────

    public function logFetch(Request $request)
    {
        try {
            $query = ActivityLog::with('account')
                ->orderBy('logged_at', 'desc');

            // Role filter
            if ($request->filled('role') && $request->role !== 'all') {
                $query->whereHas('account', function ($q) use ($request) {
                    $q->where('role', $request->role);
                });
            }

            // Category tab filter
            $category = $request->input('category', 'all');
            if ($category && $category !== 'all') {
                $query->where('category', $category);
            }

            // Keyword search
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // ── Date range filter ─────────────────────────────────────────────
            // logged_at stores Manila-local DATETIME. whereDate() compares only
            // the date part, so this is safe without timezone conversion.
            if ($request->filled('date_from')) {
                $query->whereDate('logged_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('logged_at', '<=', $request->date_to);
            }

            $logs = $query->paginate(20);

            // ── Group by Manila date label ─────────────────────────────────────
            // We use the model accessor (getLoggedAtManilaAttribute) to get a
            // correctly-labelled Manila Carbon for grouping and display.
            $groupedLogs = $logs->getCollection()
                ->groupBy(fn($log) => $log->logged_at_manila->format('l, F j, Y'))
                ->map(fn($items) => $items->map(fn($log) => $this->formatLog($log))->values());

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'grouped'    => $groupedLogs,
                    'categories' => $this->getCategories(),
                    'roles'      => $this->getRoles(),
                    'pagination' => [
                        'current_page' => $logs->currentPage(),
                        'last_page'    => $logs->lastPage(),
                        'per_page'     => $logs->perPage(),
                        'total'        => $logs->total(),
                    ],
                ],
            ]);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function showLogs(ActivityLog $activityLog)
    {
        try {
            $activityLog->load('account');

            return response()->json([
                'status' => 'success',
                'data'   => $this->formatLog($activityLog),
            ]);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function storeLogs(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id'             => 'required|exists:accounts,id',
                'action'              => 'required|string|max:255',
                'category'            => 'required|in:orders,stock,account,blogs,payments,backups,other',
                'product_name'        => 'nullable|string|max:255',
                'product_unique_code' => 'nullable|string|max:100',
                'mode_of_payment'     => 'nullable|string|max:100',
                'amount'              => 'nullable|numeric|min:0',
                'description'         => 'nullable|string',
            ]);

            $user = Account::find($validatedData['user_id']);

            $log = ActivityLog::log(
                $user,
                $validatedData['action'],
                $validatedData['category'],
                $validatedData
            );

            return response()->json([
                'status'  => 'success',
                'data'    => $log,
                'message' => 'Activity logged.',
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => 'error', 'type' => 'validation', 'message' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function updateLogs(Request $request, ActivityLog $activityLog)
    {
        try {
            $validatedData = $request->validate([
                'action'              => 'sometimes|string|max:255',
                'category'            => 'sometimes|in:orders,stock,account,blogs,payments,backups,other',
                'product_name'        => 'nullable|string|max:255',
                'product_unique_code' => 'nullable|string|max:100',
                'mode_of_payment'     => 'nullable|string|max:100',
                'amount'              => 'nullable|numeric|min:0',
                'reference_table'     => 'nullable|string|max:100',
                'reference_id'        => 'nullable|integer',
                'description'         => 'nullable|string',
            ]);

            $activityLog->update($validatedData);

            return response()->json([
                'status'  => 'success',
                'message' => 'Activity log updated successfully.',
                'data'    => $activityLog->fresh()->load('account'),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => 'error', 'type' => 'validation', 'message' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroyLogs(ActivityLog $activityLog)
    {
        try {
            $activityLog->delete();
            return response()->json(['status' => 'success', 'message' => 'Activity log deleted successfully.']);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function delallLogs(Request $request)
    {
        try {
            $category = $request->input('category', 'all');

            if ($category === 'all') {
                ActivityLog::truncate();
                $message = 'All activity logs cleared.';
            } else {
                ActivityLog::where('category', $category)->delete();
                $message = ucfirst($category) . ' activity logs cleared successfully.';
            }

            return response()->json(['status' => 'success', 'message' => $message]);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Serialize a single ActivityLog into the shape the frontend expects.
     *
     * Uses the model's getLoggedAtManilaAttribute accessor to get a reliably
     * Manila-time Carbon regardless of how the DB driver parses DATETIME values.
     */
    private function formatLog(ActivityLog $log): array
    {
        // Use the model accessor — this handles both UTC-stored and local-stored
        // DATETIME columns correctly via shiftTimezone().
        $manila = $log->logged_at_manila;

        return [
            'id'                  => $log->activity_id,
            'user_name'           => $log->user_name,
            'role'                => $log->account->role ?? 'user',
            'action'              => $log->action,
            'category'            => $log->category,
            'product_name'        => $log->product_name,
            'product_unique_code' => $log->product_unique_code,
            'amount'              => $log->amount,
            'description'         => $log->description,
            'mode_of_payment'     => $log->mode_of_payment,
            'reference_table'     => $log->reference_table,
            'reference_id'        => $log->reference_id,

            // ── Timestamps — all Manila-local ──────────────────────────────
            // "Jul 15 at 2:30 PM"
            'logged_at'      => $manila->format('M d \a\t g:i A'),
            // "2:30 PM"
            'logged_at_time' => $manila->format('g:i A'),
            // Full ISO 8601 with +08:00 offset — useful for frontend date math
            'logged_at_iso'  => $manila->toIso8601String(),
            // Raw date string (YYYY-MM-DD) for grouping fallback
            'logged_at_date' => $manila->format('Y-m-d'),
        ];
    }

    private function getCategories(): array
    {
        return [
            ['key' => 'all',      'label' => 'All'],
            ['key' => 'orders',   'label' => 'Orders'],
            ['key' => 'stock',    'label' => 'Stock'],
            ['key' => 'account',  'label' => 'Account'],
            ['key' => 'blogs',    'label' => 'Blogs'],
            ['key' => 'payments', 'label' => 'Payments'],
            ['key' => 'backups',  'label' => 'Backups'],
        ];
    }

    private function getRoles(): array
    {
        return [
            ['key' => 'all',   'label' => 'All'],
            ['key' => 'admin', 'label' => 'Admin'],
            ['key' => 'user',  'label' => 'User'],
        ];
    }
}