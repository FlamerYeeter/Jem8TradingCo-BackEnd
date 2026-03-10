<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\admin_leadership;


class AdminLeadershipController extends Controller
{
    public function adminImgIndex(Request $request){
        try{

        $query = admin_leadership::with('leader');

        if ($request -> has('leader')){
            $leaders = $request->input('leader');

            $query->whereHas('leader', function($q) use ($leaders){
                $q->where('id', $leaders);
            });
        }

        $leaderships = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $leaderships
        ], 200);

        }catch(\Exception $e){
            return response()->json([
                    'status' => 'error',
                    'type' => 'server',
                    'message' => $e->getMessage()
                ], 500);
        }

        
    }

    public function adminImgStore(Request $request){
        try {
            $request->validate([
                'user_id'       => 'required|exists:users,id',
                'position'      => 'required|string|max:255',
                'status'        => 'required|in:active,inactive',
                'leadership_img'=> 'required|image|mimes:j  peg,png,jpg|max:2048',
            ]);

            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('leadership_img')) {
                $imagePath = $request->file('leadership_img')->store('leadership_imgs', 'public');
            }

            $leadership = admin_leadership::create([
                'user_id'        => $request->user_id,
                'position'       => $request->position,
                'status'         => $request->status,
                'leadership_img' => $imagePath,
            ]);

            return response()->json([
                'status' => 'success',
                'data'   => $leadership,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'validation',
                'message' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'server',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function adminImgUpdate(Request $request, $id){
        try {

            

            $leadership = admin_leadership::findOrFail($id);

            $request->validate([
                'user_id'       => 'sometimes|exists:users,id',
                'position'      => 'sometimes|string|max:255',
                'status'        => 'sometimes|in:active,inactive',
                'leadership_img'=> 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            ]);


            $data = $request->only(['user_id','position','status']);

            if ($request->hasFile('leadership_img')) {
                // Delete old image if exists
                if ($leadership->leadership_img) {
                    \Storage::disk('public')->delete($leadership->leadership_img);
                }
                // Store new image
                $data['leadership_img'] = $request->file('leadership_img') 
                -> store('leadership_imgs','public');
            }

            $leadership->update($data);

            return response()->json([
                'status' => 'success',
                'data'   => $leadership,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'not_found',
                'message' => 'Leadership entry not found'
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'validation',
                'message' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'server',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function adminImgDelete($id){
        try {
            $leadership = admin_leadership::findOrFail($id);

            // Delete image if exists
            if ($leadership->leadership_img) {
                \Storage::disk('public')->delete($leadership->leadership_img);
            }

            $leadership->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Leadership entry deleted successfully',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'not_found',
                'message' => 'Leadership entry not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'server',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    


}