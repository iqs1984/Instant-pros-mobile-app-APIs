<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Validator;
use App\Models\User;

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
}
