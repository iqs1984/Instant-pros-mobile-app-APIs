<?php

namespace App\Http\Controllers;
use Auth;
use Validator;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\UserSecondaryAddress;


class AddressController extends Controller
{
    public function getAllCountries(Request $request){

        $countries = Country::where('status','1')->get();

        if(count($countries) > 0){
            return response()->json([
                "message" => "success",
                "data" => $countries
            ]);
        }else{
            return response()->json([
                "message" => "error",
                "data" => "No country found"
            ]);
        }
    }

    public function getAllStates(Request $request)
    {
        if($request->country_id != null)
        {
            $states = Country::find($request->country_id)->State()->where('country_id', $request->country_id)->get();

            if(count($states) > 0){
                return response()->json([
                    "message" => "success",
                    "data" => $states
                ]);
            }else{
                return response()->json([
                    "message" => "error",
                    "data" => "No state found"
                ]);
            }
        }else{
            return response()->json([
                "message" => "error",
                "data" => "Pass country_id parameter value"
            ]);
        }
    }

    public function getAllCities(Request $request)
    {
        if($request->state_id != null)
        {
            $cities = State::find($request->state_id)->city()->where('state_id', $request->state_id)->get();

            if(count($cities) > 0){
                return response()->json([
                    "message" => "success",
                    "data" => $cities
                ]);
            }else{
                return response()->json([
                    "message" => "error",
                    "data" => "No city found"
                ]);
            }
        }else{
            return response()->json([
                "message" => "error",
                "data" => "Pass state_id parameter value"
            ]);
        }
    }

    public function saveUserSecondaryAddress(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'street_address' => 'required|string',
            'city_id'        => 'required|integer',
            'state_id'       => 'required|integer',
            'country_id'     => 'required|integer',
            'zip_code'       => 'required|integer',  
            'longitude'      => 'required|string',  
            'latitude'       => 'required|string',  
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $country_name   = Country::find($request->country_id);
        $state_name     = State::find($request->state_id);
        $city_name      = City::find($request->city_id);

        $saveAddress = UserSecondaryAddress::create([
            'user_id'           => $user->id,
            'street_address'    => $request->street_address,
            'city_id'           => $request->city_id,
            'city_name'         => $city_name['city_name'],
            'state_id'          => $request->state_id,
            'state_name'        => $state_name['state_name'],
            'country_id'        => $request->country_id,
            'country_name'      => $country_name['country_name'],
            'zip_code'          => $request->zip_code,
            'longitude'         => $request->longitude,
            'latitude'          => $request->latitude,
        ]);

        if($saveAddress){
            return response()->json(['success'=> true, 'message' => 'address added successfully', 'data' => $saveAddress], 200);
        }else{
            return response()->json(['success'=> false, 'message' => 'something went wrong'], 400);
        }
    }

    public function updateUserSecondaryAddress(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'address_id'        => 'required|integer',
            'street_address'    => 'required|string',
            'city_id'           => 'required|integer',
            'state_id'          => 'required|integer',
            'country_id'        => 'required|integer',
            'zip_code'          => 'required|integer',
            'longitude'         => 'required|string',  
            'latitude'          => 'required|string',  
        ]);
        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }
        $address_details = UserSecondaryAddress::where(['id' => $request->address_id, 'user_id' => $user->id])->first();
        if($address_details){
            
            $country_name   = Country::find($request->country_id);
            $state_name     = State::find($request->state_id);
            $city_name      = City::find($request->city_id);

            $address_details->street_address = $request->street_address;
            $address_details->city_id = $request->city_id;
            $address_details->city_name = $city_name['city_name'];
            $address_details->state_id = $request->state_id;
            $address_details->state_name = $state_name['state_name'];
            $address_details->country_id = $request->country_id;
            $address_details->country_name = $country_name['country_name'];
            $address_details->zip_code = $request->zip_code;
            $address_details->longitude = $request->longitude;
            $address_details->latitude = $request->latitude;
            $address_details->save();

            return response()->json(['success'=> true, 'message' => 'address updated successfully'], 200);

        }else{
            return response()->json(['success'=> false, 'message' => 'something went wrong'], 404);
        }
    }

    public function deleteUserSecondaryAddress(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'address_id' => 'required|integer',
        ]);
        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }
        $address_delete_status = UserSecondaryAddress::where(['id' => $request->address_id, 'user_id' =>$user->id ])->delete();
        if($address_delete_status == true){
            return response()->json(['success'=>true,'message' => 'Address Deleted successfully'], 200);
        }else{
            return response()->json(['success'=>false,'message'=> 'Address not found'], 404);
        }
    }

    public function UserSecondaryAddressList(Request $request)
    {
        $user = auth()->user();
        $address_details = UserSecondaryAddress::where('user_id', $user->id)->get();
        if($address_details){
            $address_list = array();
            foreach($address_details as $address){
                $complete_address = $address->street_address.', '.$address->city_name.', '.$address->state_name.', '.$address->country_name.', '.$address->zip_code;
    
                $address_list []= array_merge($address->toArray(),['complete_address' => $complete_address]);
            }
            return response()->json(['success'=>true,'data' => $address_list], 200);
        }else{
            return response()->json(['success'=>false,'data'=> 'No Secondary Address Found!'], 404);
        }
    }
}
