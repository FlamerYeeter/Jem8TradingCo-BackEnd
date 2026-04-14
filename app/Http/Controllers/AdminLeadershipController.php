<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\admin_leadership;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminLeadershipController extends Controller
{
    // GET ALL LEADERSHIP
    public function adminImgIndex(Request $request)
    {
        try {
            $query = admin_leadership::query();

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $leaderships = $query->get();

            // ✅ Log: viewed leadership list
            // ActivityLog::log(Auth::user(), 'Viewed leadership list', 'account', [
            //     'description'     => Auth::user()->first_name . ' viewed the leadership list',
            //     'reference_table' => 'admin_leaderships',
            // ]);

            return response()->json([
                'status' => 'success',
                'data'   => $leaderships
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }

    // CREATE LEADERSHIP
    public function adminImgStore(Request $request)
    {
        try {
            $request->validate([
                'name'           => 'required|string|max:255',
                'position'       => 'required|string|max:255',
                'status'         => 'required|boolean',
                'leadership_img' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $imagePath = null;

            if ($request->hasFile('leadership_img')) {
                $uploadPath = public_path('storage/leadership_imgs');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                $filename  = time() . '_' . uniqid() . '.' . $request->file('leadership_img')->getClientOriginalExtension();
                $request->file('leadership_img')->move($uploadPath, $filename);
                $imagePath = 'leadership_imgs/' . $filename;
            }

            $leadership = admin_leadership::create([
                'name'           => $request->name,
                'position'       => $request->position,
                'status'         => $request->status,
                'leadership_img' => $imagePath,
            ]);

            // ✅ Log: created leadership
            // ActivityLog::log(Auth::user(), 'Added a leadership member', 'account', [
            //     'description'     => Auth::user()->first_name . ' added leadership member: ' . $request->name . ' as ' . $request->position,
            //     'reference_table' => 'admin_leaderships',
            //     'reference_id'    => $leadership->id,
            // ]);

            return response()->json([
                'status'    => 'success',
                'data'      => $leadership,
                'image_url' => asset('storage/' . $imagePath)
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // UPDATE LEADERSHIP
    public function adminImgUpdate(Request $request, $id)
    {
        try {
            $leadership = admin_leadership::findOrFail($id);

            $request->validate([
                'name'           => 'sometimes|string|max:255',
                'position'       => 'sometimes|string|max:255',
                'status'         => 'sometimes|boolean',
                'leadership_img' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $data = $request->only(['name', 'position', 'status']);

            if ($request->hasFile('leadership_img')) {
                $uploadPath = public_path('storage/leadership_imgs');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                if ($leadership->leadership_img) {
                    $oldPath = public_path('storage/' . $leadership->leadership_img);
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $filename          = time() . '_' . uniqid() . '.' . $request->file('leadership_img')->getClientOriginalExtension();
                $request->file('leadership_img')->move($uploadPath, $filename);
                $data['leadership_img'] = 'leadership_imgs/' . $filename;
            }

            $leadership->update($data);

            // ✅ Log: updated leadership
            // ActivityLog::log(Auth::user(), 'Updated a leadership member', 'account', [
            //     'description'     => Auth::user()->first_name . ' updated leadership member: ' . $leadership->name,
            //     'reference_table' => 'admin_leaderships',
            //     'reference_id'    => $id,
            // ]);

            return response()->json([
                'status'    => 'success',
                'data'      => $leadership,
                'image_url' => $leadership->leadership_img ? asset('storage/' . $leadership->leadership_img) : null
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'type' => 'not_found', 'message' => 'Leadership entry not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => 'error', 'type' => 'validation', 'message' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }

    // DELETE LEADERSHIP
    public function adminImgDelete($id)
    {
        try {
            $leadership = admin_leadership::findOrFail($id);
            $name       = $leadership->name;

            if ($leadership->leadership_img) {
                Storage::disk('public')->delete($leadership->leadership_img);
            }

            $leadership->delete();

            // ✅ Log: deleted leadership
            // ActivityLog::log(Auth::user(), 'Deleted a leadership member', 'account', [
            //     'description'     => Auth::user()->first_name . ' deleted leadership member: ' . $name,
            //     'reference_table' => 'admin_leaderships',
            //     'reference_id'    => $id,
            // ]);

            return response()->json(['status' => 'success', 'message' => 'Leadership entry deleted successfully'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'type' => 'not_found', 'message' => 'Leadership entry not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }
}