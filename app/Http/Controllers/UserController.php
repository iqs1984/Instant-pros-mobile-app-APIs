<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Validator;
use App\Models\User;
use App\Models\UserFcmTokens;

class UserController extends Controller
{
    public function getUserDetails(Request $request)
    {
        $allUserDetails = auth('api')->user();

        if($allUserDetails->role == 'user'){
            $user = auth('api')->user()->only(['id', 'name', 'email', 'role','phone','address','chatUserId','created_at','updated_at']);

            return response()->json(['user'=>$user], 201);
        }else{
            $vendor = auth('api')->user()->only(['id', 'business_name', 'email','role', 'category','business_logo','country_id','country_name','state_id','state_name','city_id','city_name','address','zip_code','chatUserId','created_at','updated_at']);

            return response()->json(['user'=>$vendor], 201);
        }
    }

    public function getUserFcmTokens(Request $request)
    {
        $user = auth()->user();
        
        $getFcmTokenList =  UserFcmTokens::where('user_id',$user->id)->get();

        if(count($getFcmTokenList) > 0){
            return response()->json(['user_fcm_token_list'=>$getFcmTokenList], 200);
        }else{
            return response()->json(['user_fcm_token_list'=>'No fcm token found'], 201);
        }
    }


    public function UpdateUserFcmTokens(Request $request)
    {
        $user = auth()->user();

        if($request->user_id == $user->id)
        {
            $user_fcm_list =  UserFcmTokens::where(['user_id' => $request->user_id, 'device_id' => $request->device_id])->first();
            if($user_fcm_list)
            {
                $user_fcm_list->fcm_token = $request->fcm_token;
                $update_data = $user_fcm_list->save();
                return response()->json(['success'=> 'FCM token updated successfully'], 200);
            }else{
                $user_fcm_data = array( 'user_id'   => $request->user_id, 
                                        'device_id' => $request->device_id,
                                        'fcm_token' => $request->fcm_token);
                
                $create_data = UserFcmTokens::create($user_fcm_data);

                return response()->json(['success' => 'FCM token data created successfully',
                                         'data'    => $create_data], 200);
            }
        }else{
            return response()->json(['error'=>'given user_id does not match with login user_id'], 201);
        }
    }
}
