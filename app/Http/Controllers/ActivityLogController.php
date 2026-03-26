<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Account;
use App\Models\Product;
use Exception;

class ActivityLogController extends Controller
{
    public function logFetch(Request $request)
    {
        try {
            $query = ActivityLog::with('account') // ✅ 'account' not 'user'
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

            // Search
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Date range
            if ($request->filled('date_from')) {
                $query->whereDate('logged_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('logged_at', '<=', $request->date_to);
            }

            $logs = $query->paginate(20);

            $groupedLogs = $logs->getCollection()
                ->groupBy(fn($log) => $log->logged_at->format('l, F j, Y'))
                ->map(fn($items) => $items->map(fn($log) => [
                    'id'                  => $log->activity_id,
                    'user_name'           => $log->user_name,
                    'role'                => $log->account->role ?? 'user', // ✅ 'account' not 'user'
                    'action'              => $log->action,
                    'category'            => $log->category,
                    'product_unique_code' => $log->product_unique_code,
                    'amount'              => $log->amount,
                    'description'         => $log->description,
                    'mode_of_payment'     => $log->mode_of_payment,
                    'reference_table'     => $log->reference_table,
                    'reference_id'        => $log->reference_id,
                    'logged_at'           => $log->logged_at->format('M d \a\t g:i A'),
                    'logged_at_time'      => $log->logged_at->format('g:i A'),
                ])->values());

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
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function showLogs(ActivityLog $activityLog)
    {
        try {
            $activityLog->load('account'); // ✅ 'account' not 'user'

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'id'                  => $activityLog->activity_id,
                    'user_name'           => $activityLog->user_name,
                    'role'                => $activityLog->account->role ?? 'user', // ✅ 'account' not 'user'
                    'action'              => $activityLog->action,
                    'category'            => $activityLog->category,
                    'product_unique_code' => $activityLog->product_unique_code,
                    'amount'              => $activityLog->amount,
                    'description'         => $activityLog->description,
                    'mode_of_payment'     => $activityLog->mode_of_payment,
                    'reference_table'     => $activityLog->reference_table,
                    'reference_id'        => $activityLog->reference_id,
                    'logged_at'           => $activityLog->logged_at->format('M d \a\t g:i A'),
                ],
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeLogs(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id'             => 'required|exists:accounts,id', // ✅ accounts not users
                'action'              => 'required|string|max:255',
                'category'            => 'required|in:orders,stock,account,blogs,payments,backups,other',
                'product_name'        => 'nullable|string|max:255',
                'product_unique_code' => 'nullable|string|max:100',
                'mode_of_payment'     => 'nullable|string|max:100',
                'amount'              => 'nullable|numeric|min:0',
                'description'         => 'nullable|string',
            ]);

            $user = Account::find($validatedData['user_id']); // ✅ Account not User

            $log = ActivityLog::log(
                $user,
                $validatedData['action'],
                $validatedData['category'],
                $validatedData
            );

            return response()->json([
                'status'  => 'success',
                'data'    => $log,
                'message' => 'Activity Logged.',
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'validation',
                'message' => $e->errors(),
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'server',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

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
                'data'    => $activityLog->fresh()->load('account'), // ✅ 'account' not 'user'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'validation',
                'message' => $e->errors(),
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'server',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyLogs(ActivityLog $activityLog)
    {
        try {
            $activityLog->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Activity log deleted successfully.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
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

            return response()->json([
                'status'  => 'success',
                'message' => $message,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
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