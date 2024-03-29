<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use Auth;
use Mail;
use Validator;
use App\Models\User;
use App\Models\Category;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\PasswordRest;
use App\Models\SocialLogin;

class AuthController extends Controller
{
    public function _construct(){
        $this->middleware('auth:api', ['except' => ['login','signup']]);
    }

    public function signup(Request $request)
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
                'phone'      => 'required|string',
                'address'    => 'required|string',
                'longitude'    => 'required|string',
                'latitude'    => 'required|string',
            ]);
    
        }else if($request->role == 'vendor'){

            $validator = Validator::make($request->all(), [
                'role'          => 'required|string',
                'email'         => 'required|string|email|unique:users',
                'password'      => 'required|string|confirmed|min:6',
                'category_id'   => 'required|integer',
                'business_name' => 'required|string',
                'phone'         => 'required|string',
                'country_id'    => 'required|integer',
                'state_id'      => 'required|integer',
                'city_id'       => 'required|integer',
                'address'       => 'required|string',
                'zip_code'      => 'required|string',                
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
            $category  = Category::find($request->category_id);
            $country_name   = Country::find($request->country_id);
            $state_name     = State::find($request->state_id);
            $city_name      = City::find($request->city_id);

            $user = User::create(array_merge(
                $validator->validated(),
                [   'country_name'  => $country_name['country_name'],
                    'state_name'    => $state_name['state_name'],
                    'city_name'     => $city_name['city_name'],
                    'category_name' => $category['category_name'],
                    'password'      => bcrypt($request->password)]
            ));
        }

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
            'message' => ucwords($request->role).' Successfully Register',
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expire_in' => JWTAuth::factory()->getTTL()*60,
        ],200);
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'role' => 'required|string'
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

        $user = auth()->user();

        if($user->role == $request->role)
        {
            if($user->role == 'user'){
                return response()->json([
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expire_in' => JWTAuth::factory()->getTTL()*60,
                    'user_id' => $user->id,
                ], 200);
    
            }else{
                return response()->json([
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expire_in' => JWTAuth::factory()->getTTL()*60,
                    'user_id' => $user->id,
                    'is_published' => $user->is_published,
                ], 200);
            }

        }else{

            if($request->role == 'user')
            {
                return response()->json(['status' => false, 'error' => 'Please use these credential with vendor login'], 400);
            }else{
                return response()->json(['status' => false, 'error' => 'Please use these credential with user login'], 400);
            }
        }
    }

    public function socialLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'email',
            'role' =>'required|string|in:user,vendor',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'social_user_id' => 'required|string',
            'login_type' => 'required|integer'
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        switch ($request->login_type) 
        {
            case 1:
                $login_type = 'LOGIN_TYPE_GOOGLE';
                break;
            case 2:
                $login_type = 'LOGIN_TYPE_FACEBOOK';
                break;
            case 3:
                $login_type = 'LOGIN_TYPE_APPLE_ID';
                break;
        }

        if($request->email != null)
        {
            $user = User::where('email', $request->email)->first();

            if($user)
            {
                $isSocialID = SocialLogin::where(['social_user_id' => $request->social_user_id, 'login_type' => $login_type])->first();
                
                if($isSocialID)
                {
                    $token = JWTAuth::fromUser($user);

                    return response()->json([
                        'token' => $token,
                        'token_type' => 'bearer',
                        'expire_in' => JWTAuth::factory()->getTTL()*60,
                        'user_id' => $user->id,
                        'is_published' => $user->is_published,
                    ], 200);

                }else{

                    $socialLoginDetails = [
                        'user_id' => $user->id,
                        'social_user_id' => $request->social_user_id,
                        'login_type' => $login_type,
                    ];
                    SocialLogin::create($socialLoginDetails);

                    $token = JWTAuth::fromUser($user);

                    return response()->json([
                        'token' => $token,
                        'token_type' => 'bearer',
                        'expire_in' => JWTAuth::factory()->getTTL()*60,
                        'user_id' => $user->id,
                        'is_published' => $user->is_published,
                    ], 200);

                }
                
            }else{

                if($request->role == 'user')
                {
                    $userDetails = [
                        'email' => $request->email,
                        'role' => $request->role,
                        'name' => $request->first_name .' '.$request->last_name,
                    ];
                    $user = User::create($userDetails);
                
                }else if($request->role == 'vendor')
                {
                    $vendorDetails = [
                        'email' => $request->email,
                        'role' => $request->role,
                        'business_name' => $request->first_name .' '.$request->last_name,
                    ];
                    $user = User::create($vendorDetails);
                }

                $socialLoginDetails = [
                    'user_id' => $user->id,
                    'social_user_id' => $request->social_user_id,
                    'login_type' => $login_type,
                ];
                SocialLogin::create($socialLoginDetails);

                $token = JWTAuth::fromUser($user);

                return response()->json([
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expire_in' => JWTAuth::factory()->getTTL()*60,
                    'user_id' => $user->id,
                    'is_published' => $user->is_published,
                ], 200);
            }
           
        }else{
            $isSocialID = SocialLogin::where(['social_user_id' => $request->social_user_id, 'login_type' => $login_type])->first();
            if($isSocialID)
            {
                $user = User::where('id', $isSocialID->user_id)->first();
                $token = JWTAuth::fromUser($user);

                return response()->json([
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expire_in' => JWTAuth::factory()->getTTL()*60,
                    'user_id' => $user->id,
                    'is_published' => $user->is_published,
                ], 200);

            }else{

                if($request->role == 'user')
                {
                    $userDetails = [
                        'role' => $request->role,
                        'name' => $request->first_name .' '.$request->last_name,
                    ];
                    $user = User::create($userDetails);
                    
                }else if($request->role == 'vendor')
                {

                    $vendorDetails = [
                        'role' => $request->role,
                        'business_name' => $request->first_name .' '.$request->last_name,
                    ];
                    $user = User::create($vendorDetails);
                }

                $socialLoginDetails = [
                    'user_id' => $user->id,
                    'social_user_id' => $request->social_user_id,
                    'login_type' => $login_type,
                ];
                SocialLogin::create($socialLoginDetails);

                $token = JWTAuth::fromUser($user);

                return response()->json([
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expire_in' => JWTAuth::factory()->getTTL()*60,
                    'user_id' => $user->id,
                    'is_published' => $user->is_published,
                ], 200);
            }
        }
    }


    public function changePassword(Request $request)
    {
        $user = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|min:6',
            'password'     => 'required|string|confirmed|min:6',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        if (!Hash::check($request->input('old_password'), $user->password)) {
            return response()->json(['status' => false, 'error' => 'Old password does not matched'], 401);
        }

        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json(['status' => true,'message' => 'Password changed successfully'],200);
    }


    public function forgotPasswordMail(Request $request){

        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $check_email = User::where('email',$request->email)->first();

        if($check_email){

            $username = $check_email->role == 'user' ? $check_email->name : $check_email->business_name;
            $secret_code = substr(number_format(time() * rand(),0,'',''),0,8);

            $password_reset_data = PasswordRest::where('email',$request->email)->first();

            if($password_reset_data){
                $password_reset_data->token = $secret_code;
                $password_reset_data->save();
            }else{
                PasswordRest::create(['email' => $request->email, 'token' => $secret_code]);

            }

            $user['to'] = $check_email->email;

            try {

                Mail::send('forgotPassword',['username' => $username, 'secret_code' => $secret_code], function ($message) use ($user) {
                    $message->to($user['to']);
                    $message->subject("Forgot Password");
                });

                return response()->json(['status' => true, 'message' => 'Secret code sent successfully'],200);
                
            } catch (\Exception $e) {
                
                return response()->json(['status' => false, 'error' => $e->getMessage()],500);
            }
                
        }else{
            return response()->json(['status' => false,'message' => 'Please enter correct email id'],200);
        }
    }

    public function resetPassword(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'secret_code'   => 'required|string|min:6',
            'password'      => 'required|string|confirmed|min:6',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $password_reset_data = PasswordRest::where('token',$request->secret_code)->first();
        
        if($password_reset_data){
            $email =  $password_reset_data['email'];

            $user = User::where('email',$email)->first();
            $user->password = bcrypt($request->password);
            $user->save();
            $password_reset_data->delete();
            
            return response()->json(['status' => true,'message' => 'Password reset successfully'],200);
        }else{
            return response()->json(['status' => false,'message' => 'Please enter correct secret code'],500);
        }
    }
}
