<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\State;
use App\Models\City;

class AddressController extends Controller
{
    public function getAllCountries(Request $request){

        $countries = Country::where('status','0')->get();

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
}
