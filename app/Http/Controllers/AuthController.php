<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Auth;
use Validator;
use App\Models\User;
use App\Models\Country;
use App\Models\State;
use App\Models\City;

class AuthController extends Controller
{
    public function _construct(){
        $this->middleware('auth:api', ['except' => ['login','register']]);
    }

    public function register(Request $request)
    {
        $country_name = "";
        $state_name = "";
        $city_name = "";

        if($request->role == 'user'){

            $validator = Validator::make($request->all(), [
                'role'       => 'required|string',
                'name'       => 'required|string',
                'email'      => 'required|string|email|unique:users',
                'password'   => 'required|string|confirmed|min:6',
                'phone'      => 'required|integer',
                'address'    => 'required|string',
            ]);
    
        }else if($request->role == 'vendor'){

            $country_name = Country::find($request->country_id)->first('country_name');
            $state_name = State::find($request->state_id)->first('state_name');
            $city_name = City::find($request->city_id)->first('city_name');

            $validator = Validator::make($request->all(), [
                'role'          => 'required|string',
                'email'         => 'required|string|email|unique:users',
                'password'      => 'required|string|confirmed|min:6',
                'category'      => 'required|integer',
                'business_name' => 'required',
                'country_id'    => 'required|integer',
                'state_id'      => 'required|integer',
                'city_id'       => 'required|integer',
                'address'       => 'required|string',
                'zip_code'      => 'required|integer',                
            ]);

        }else{
            return response()->json(array(
                'error'    =>  400,
                'message'  =>  "please use valid role user/vendor"
            ), 400);
        }

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        if($request->role == 'user'){
            $user = User::create(array_merge(
                $validator->validated(),
                ['password' => bcrypt($request->password)]
            ));
        }else{
            $user = User::create(array_merge(
                $validator->validated(),
                [   'country_name'  => $country_name['country_name'],
                    'state_name'    => $state_name['state_name'],
                    'city_name'     => $city_name['city_name'],
                    'password'      => bcrypt($request->password)]
            ));
        }

        return response()->json([
            'message' => ucwords($request->role).' Successfully Register',
            'user' => $user,
        ],200);

    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'error' => 'Invalid Credentials'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Could not create token'
            ], 500);
        }
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expire_in' => JWTAuth::factory()->getTTL()*60,
        ], 200);
    }

}
