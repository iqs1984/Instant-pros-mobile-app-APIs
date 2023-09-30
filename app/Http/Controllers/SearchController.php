<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Models\User;
use App\Models\VendorServices;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Category;
use App\Models\Order;

class SearchController extends Controller
{
    
    public function vendorSearch(Request $request)
    {
        $perPage = 20;
        $validator = Validator::make($request->all(), [
            'keyword' => 'required|string',
            'page' => 'required|integer',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $keyword = $request->keyword;

        $vendorsList = User::where('business_name', 'like', "%$keyword%")->orWhereHas('services', function ($query) use ($keyword) {
                            $query->where('title', 'like', "%$keyword%");})->paginate($perPage);

        if(count($vendorsList) > 0)
        {
            foreach($vendorsList as $vendor)
            {
                $count = $vendor->reviews()->count();
                if($count > 0){
                    $avgRating = ['avgRating' => number_format(floatval($vendor->reviews()->sum('rating')/$vendor->reviews()->count()),2,'.','')];
                }else{
                    $avgRating = ['avgRating' => null];
                }
                $price = ['minPrice' => $vendor->services()->min('price'),'maxPrice' => $vendor->services()->max('price')];
                $data[] = array_merge(json_decode($vendor, true),$price,$avgRating);
            }

            return response()->json(['success'=> true, 'data' =>$data ], 200);

        }else{
            return response()->json(['success'=> false, 'message' =>'No Vendor Found!'], 500);
        }
    }

    public function searchByLocation(Request $request)
    {
        $perPage = 20;
        $validator = Validator::make($request->all(), [
            'keyword' => 'required|string',
            'page' => 'required|integer',

        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $keyword = $request->keyword;

        $vendorList = User::where(function ($query) use ($keyword) {
            $query->where('city_name', 'LIKE', '%' . $keyword . '%')->orWhere('state_name', 'LIKE', '%' . $keyword . '%');
        })->paginate($perPage);

        if(count($vendorList) > 0)
        {
            foreach($vendorList as $vendor)
            {
                $count = $vendor->reviews()->count();
                if($count > 0){
                    $avgRating = ['avgRating' => number_format(floatval($vendor->reviews()->sum('rating')/$vendor->reviews()->count()),2,'.','')];
                }else{
                    $avgRating = ['avgRating' => null];
                }
                $price = ['minPrice' => $vendor->services()->min('price'),'maxPrice' => $vendor->services()->max('price')];
                $data[] = array_merge(json_decode($vendor, true),$price,$avgRating);
            }

            return response()->json(['success'=> true, 'data' =>$data ], 200);

        }else{
            return response()->json(['success'=> false, 'message' =>'No Vendor Found!'], 500);
        }

    }
}
