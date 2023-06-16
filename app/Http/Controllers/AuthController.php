<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
                'role' => 'required|string',
                'name' => 'required|string',
                'email' => 'required|string|email|unique:users',
                'password' => 'required|string|confirmed|min:6',
                'phone' => 'required|integer',
                'address' => 'required|string',
            ]);
    
        }else if($request->role == 'vendor'){

            $country_name = Country::find($request->country_id)->first('country_name');
            $state_name = State::find($request->state_id)->first('state_name');
            $city_name = City::find($request->city_id)->first('city_name');

            $validator = Validator::make($request->all(), [
                'role' => 'required|string',
                'email' => 'required|string|email|unique:users',
                'password' => 'required|string|confirmed|min:6',
                'category' => 'required|integer',
                'business_name' => 'required',
                'country_id' => 'required|integer',
                'state_id' => 'required|integer',
                'city_id' => 'required|integer',
                'address' => 'required|string',
                'zip_code' => 'required|integer',
                
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
        $validator = Validator::make($request->all(), [
            
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if($validator->fails()){
            return $validator->messages()->toJson();
        }

        $user = User::where('email', $request->email)->first();

        if(!$user){
            return response()->json(['error' => 401,'message' => 'Email not found' ],401);
        } 
        if(!$token = auth()->attempt($validator->validated())){
            return response()->json(['error' => 401,'message' => 'Incorrect password'],401);
        }

        return $this->createNewToken($token);
    }

    public function createNewToken($token)
    {
        if(auth()->user()['role'] == 'user')
        {
            return response()->json([
                'access_token'  => $token,
                'token_type'    => 'bearer',
                'expire_in'     => auth()->factory()->getTTL()*60,
                'user'          => auth()->user()->only(['id', 'name', 'email', 'role','phone','address','created_at','updated_at'])
            ]);

        }else if(auth()->user()['role'] == 'vendor')
        {
            return response()->json([
                'access_token'  => $token,
                'token_type'    => 'bearer',
                'expire_in'     => auth()->factory()->getTTL()*60,
                'user'          => auth()->user()->only(['id', 'business_name', 'email','role', 'category','business_logo','country_id','country_name','state_id','state_name','city_id','city_name','address','zip_code','created_at','updated_at'])
            ]);
        }

    }
}
