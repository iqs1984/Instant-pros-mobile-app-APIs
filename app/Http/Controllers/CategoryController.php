<?php

namespace App\Http\Controllers;
use Auth;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function getAllCategories(Request $request){
 
        $categories = Category::where('status',1)->get();

        if(count($categories) > 0){
            return response()->json([
                "message" => "success",
                "data" => $categories
            ]);
        }else{
            return response()->json([
                "message" => "error",
                "data" => "No category found"
            ]);
        }
    }


    // public function test(Request $request, $id){
    //     dd(request()->id);
        
        // $categories = Category::where('status',1)->get();

        // if(count($categories) > 0){
        //     return response()->json([
        //         "message" => "success",
        //         "data" => $categories
        //     ]);
        // }else{
        //     return response()->json([
        //         "message" => "error",
        //         "data" => "No category found"
        //     ]);
        // }
    // }
}
