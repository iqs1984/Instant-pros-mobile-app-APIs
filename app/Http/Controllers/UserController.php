<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Validator;
use App\Models\User;
use App\Models\UserFcmTokens;
use App\Models\VendorServices;

class UserController extends Controller
{
    public function getUserDetails(Request $request)
    {
        $allUserDetails = auth('api')->user();

        if($allUserDetails->role == 'user'){
            $user = auth('api')->user()->only(['id', 'name', 'email', 'role','phone','address','chatUserId','created_at','updated_at']);

            return response()->json(['user'=>$user], 201);
        }else{
            $vendor = auth('api')->user()->only(['id', 'business_name', 'email','role', 'category','business_logo','phone','country_id','country_name','state_id','state_name','city_id','city_name','address','zip_code','chatUserId','created_at','updated_at']);

            return response()->json(['user'=>$vendor], 201);
        }
    }

    public function getUserFcmTokens(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $getFcmTokenList = UserFcmTokens::where('user_id',$request->user_id)->get();

        if(count($getFcmTokenList) > 0){
            return response()->json(['user_fcm_token_list'=>$getFcmTokenList], 200);
        }else{
            return response()->json(['user_fcm_token_list'=>'No fcm token found'], 201);
        }
    }


    public function UpdateUserFcmTokens(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'user_id'    => 'required',
            'device_id'  => 'required|string',
            'fcm_token'  => 'required|string',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }
       
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

    public function UpdateChatUserID(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'chatUserId'    => 'required',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }
       
        if($request->user_id == $user->id)
        {
            $user->chatUserId = $request->chatUserId;
            $update_data = $user->save();
            return response()->json(['success'=> 'ChatUserId updated successfully'], 200);
        }else{
            return response()->json(['error'=>'given user_id does not match with login user_id'], 201);
        }
    }

    public function addService(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'user_id'  => 'required',
            'title'    => 'required|string',
            'price'    => 'required|string',
            'duration' => 'required|string',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        if($request->user_id == $user->id)
        {
            $service = VendorServices::create($validator->validated());

            return response()->json(['success'=> 'Service added successfully',
                                      'data' => $service], 200);
        }else{
            return response()->json(['error'=>'given user_id does not match with login user_id'], 201);
        }
    }

    public function updateService(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'service_id'  => 'required',
            'title'       => 'required|string',
            'price'       => 'required|string',
            'duration'    => 'required|string',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $service = VendorServices::where(['user_id' => $user->id ,'id' => $request->service_id])->first();

        if($service){
            $service->title    = $request->title;
            $service->price    = $request->price;
            $service->duration = $request->duration;
            $service->save();
            if($service == true){
                return response()->json(['success'=> 'Service updated successfully'], 200);
            }else{
                return response()->json(['error'=> 'Something went wrong!'], 200);
            }
        }else{
            return response()->json(['error'=>'given service_id does not belong to login user'], 201);
        }
    }

    public function deleteService(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'user_id'  => 'required',
            'service_id'  => 'required',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        if($request->user_id == $user->id){

            $deleteService = VendorServices::where(['user_id' => $user->id ,'id' => $request->service_id])->delete();
            if($deleteService == true){
                return response()->json(['success'=> 'Service deleted successfully'], 200);
            }else{
                return response()->json(['error'=> 'Something went wrong!'], 200);
            }
        }else{
            return response()->json(['error'=>'given user_id does not match with login user_id'], 201);
        }
    }
}
