<?php

namespace App\Http\Controllers;

use App\Models\adminsettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\Concerns\Has;
use App\Models\ActivityLog;

class AdminsettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settings = adminsettings::pluck('value', 'key');
        return response()->json(['data' => $settings]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'siteName'       => 'sometimes|string|max:255',
            'siteURL'        => 'sometimes|url',
            'adminEmail'     => 'sometimes|email',
            'contactNumber'  => 'sometimes|string',
            'companyAddress' => 'sometimes|string',
            'timezone'       => 'sometimes|string',
            'language'       => 'sometimes|string',
            'passwordLockout'=> 'sometimes|integer|min:1|max:99',
            'sessionTimeout' => 'sometimes|integer|min:1|max:999',
            'primaryColor'   => 'sometimes|string|max:7',
            'theme'          => 'sometimes|in:light,dark,auto',
            'require2FA'     => 'sometimes|boolean',
        ]);

        $fields = $request->only([
            'siteName', 'siteURL', 'adminEmail', 'contactNumber',
            'companyAddress', 'timezone', 'language',
            'passwordLockout', 'sessionTimeout',
            'primaryColor', 'theme', 'require2FA',
        ]);

        foreach ($fields as $key => $value) {
            adminsettings::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return response()->json(['message' => 'Settings saved successfully']);
    }

    /**
     * Display the specified resource.
     */
    public function show(adminsettings $adminsettings)
    {
        //
    }


    /*
    *Change password
    */ 
    
    public function changePassword (Request $request){
        $request -> validate ([
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:6',
            'confirmPassword' => 'required|same:newPassword',
        ]);

        $token = $request->cookie('jem8_token');
        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if(!$accessToken){
            return response()->json(['message'=>'Unanutheticated'], 401);
        }
        $user =$accessToken->tokenable;
        if (!Hash::check($request->currentPassword, $user->password)){
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }
        
        $user->update(['password' => Hash::make($request->newPassword)]);

        ActivityLog::log($user, 'Changed admin password', 'account', [
            'description'     => $user->first_name . ' changed their password',
            'reference_table' => 'accounts',
            'reference_id'    => $user->id,
        ]);

        return response()->json(['message' => 'Password updated successfully']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(adminsettings $adminsettings)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, adminsettings $adminsettings)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(adminsettings $adminsettings)
    {
        //
    }
}
