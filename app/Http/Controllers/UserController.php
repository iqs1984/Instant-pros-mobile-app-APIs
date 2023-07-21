<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Rules\StripeKeyValidator;
use Auth;
use Validator;
use App\Models\User;
use App\Models\UserFcmTokens;
use App\Models\VendorServices;
use App\Models\VendorAboutData;
use App\Models\VendorGalleryImages;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Category;
use App\Models\StripeAccountDetails;
use App\Models\DocumentText;
use App\Models\VendorReview;


class UserController extends Controller
{
    public function getUserDetails(Request $request)
    {
        $allUserDetails = auth('api')->user();

        if($allUserDetails->role == 'user'){
            $user = auth('api')->user()->only(['id', 'name', 'email', 'role','phone','profile_image','address','chatUserId','created_at','updated_at']);

            return response()->json(['user'=>$user], 201);
        }else{
            $vendor = auth('api')->user()->only(['id', 'business_name', 'email','role', 'category_id','category_name','profile_image','phone','country_id','country_name','state_id','state_name','city_id','city_name','address','zip_code','chatUserId','is_published','created_at','updated_at']);

            return response()->json(['user'=>$vendor], 201);
        }
    }

    public function UpdateUserDetails(Request $request)
    {
        $user = auth()->user();

        $country_name = "";
        $state_name = "";
        $city_name = "";

        if($user->role == 'user'){

            $validator = Validator::make($request->all(), [
                'email'      => 'required|email|unique:users,email,'.$user->id.'id',
                'name'       => 'required|string',
                'phone'      => 'required|string|unique:users,phone,'.$user->id.'phone',  
                'address'    => 'required|string',
            ]);
    
        }else if($user->role == 'vendor'){

            $validator = Validator::make($request->all(), [
                'email'         => 'required|email|unique:users,email,'.$user->id.'id',
                'business_name' => 'required|string',
                'category_id'   => 'required|string',
                'address'       => 'required|string',
                'country_id'    => 'required|integer',
                'state_id'      => 'required|integer',
                'city_id'       => 'required|integer',
                'zip_code'      => 'required|integer',
                'phone'         => 'required|string|unique:users,phone,'.$user->id.'phone',          
            ]);
        }

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        if($user->role == 'user')
        {
            $user->email = $request->email;
            $user->name = $request->name;
            $user->phone = $request->phone;
            $user->address = $request->address;
            $user->save();

        }else{

            $country_name = Country::find($request->country_id);
            $state_name = State::find($request->state_id);
            $city_name = City::find($request->city_id);
            $category = Category::find($request->category_id);

            $user->email = $request->email;
            $user->business_name = $request->business_name;
            $user->category_id = $request->category_id;
            $user->category_name = $category['category_name'];
            $user->address = $request->address;
            $user->country_id = $request->country_id;
            $user->state_id = $request->state_id;
            $user->city_id = $request->city_id;
            $user->zip_code = $request->zip_code;
            $user->phone = $request->phone;
            $user->save();
        }
        return response()->json(['message' => ucwords($user->role).' Updated Successfully'],200);
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

    public function getVendorServices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'  => 'required',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $allServices = VendorServices::where(['user_id' => $request->user_id])->get();
        
        if(count($allServices) > 0){
            return response()->json(['data'=> $allServices], 200);
        }else{
            return response()->json(['error'=> 'No services found'], 200);
        }
    }

    public function addVendorAbout(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'user_id'  => 'required',
            'description' => 'required|string'
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        if($request->user_id == $user->id)
        {
            $aboutData = VendorAboutData::where(['user_id' => $user->id])->first();
            if($aboutData){
                
                $aboutData->description = $request->description;
                $aboutData->save();
                return response()->json(['success'=> 'About updated successfully'], 200);
            }else{
                $aboutData = VendorAboutData::create($validator->validated());
                return response()->json(['success'=> 'About Added successfully',
                                         'data' => $aboutData], 200);
            }
        }else{
            return response()->json(['error'=>'given user_id does not match with login user_id'], 201);
        }
    }

    public function getVendorAbout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'  => 'required',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $aboutData = VendorAboutData::where(['user_id' => $request->user_id])->first();
        if($aboutData){
            return response()->json(['data'  => $aboutData], 200);
        }else{
            return response()->json(['error'=> 'No text found'], 200);
        }
    }

    public function uploadGalleryImage(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*' => 'image|max:2048',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $uploadedImages = [];

        if ($request->hasFile('images')) {
            $images = $request->file('images');

            foreach ($images as $image) {
                $fileName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads'), $fileName);

                $imageModel = new VendorGalleryImages();
                $imageModel->user_id = $user->id;
                $imageModel->image_name = $fileName;
                $imageModel->image_path = '/uploads/' . $fileName;
                $imageModel->save();

                $uploadedImages[] = $imageModel->image_path;
            }
            return response()->json(['message' => 'Images uploaded successfully', 'uploaded_images' => $uploadedImages]);
        }
        return response()->json(['message' => 'No image files found'], 400);
    }

    public function getGalleryImages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $images = VendorGalleryImages::where('user_id', $request->user_id)->get();
        if(count($images) > 0){
            foreach($images as $image)
            {
                $getImagesPath[] = ['item_id' => $image->id, 'image_path' => url('/').$image->image_path];
            }
            return response()->json(['Gallery_images' => $getImagesPath]);
        }else{
            return response()->json(['message' => 'No Image found']);
        }
    }

    public function deleteGalleryImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'item_id' => 'required',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $image = VendorGalleryImages::where(['user_id' => $request->user_id,'id' => $request->item_id])->first();

        if (File::exists(public_path($image['image_path']))) {
            File::delete(public_path($image['image_path']));
            $image->delete();
            return response()->json(['message' => 'Image deleted successfully']);
        }else{
            return response()->json(['message' => 'No Image found']);
        }
    }

    public function updateProfileImage(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'profile_image' => 'mimes:jpeg,jpg,png',
            'profile_image' => 'image|max:2048',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        if ($request->hasFile('profile_image')) {
            $profileImage = $request->file('profile_image');
            $fileName = time() . '_' . $profileImage->getClientOriginalName();
            $profileImage->move(public_path('uploads'), $fileName);
            
            if (File::exists(public_path($user['profile_image']))) {
                File::delete(public_path($user['profile_image']));
            }

            $user->profile_image = '/uploads/' . $fileName;
            $user->save();

            return response()->json(['message' => 'Profile Image uploaded successfully']);
        }else{
            return response()->json(['message' => 'No Image found']);
        }
    }

    public function addStripeAccount(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'publishable_key' => ['required', new StripeKeyValidator],
            'secret_key' => ['required', new StripeKeyValidator],
            'user_id' =>'required'
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        if($request->user_id == $user->id)
        {
            $StripeAccDetails = StripeAccountDetails::where('user_id',$request->user_id)->first();

            if($StripeAccDetails){
                
                $StripeAccDetails->publishable_key = $request->publishable_key;
                $StripeAccDetails->secret_key = $request->secret_key;
                $StripeAccDetails->user_id = $request->user_id;
                $StripeAccDetails->save();

                return response()->json(['success'=> 'Stripe Account Details updated successfully'],200);

            }else{
                $StripeAccDetails = StripeAccountDetails::create(array_merge(
                    $validator->validated(),
                    ['user_id'  => $user->id]
                ));
            }
        
            return response()->json(['success'=> 'Stripe Account Details save successfully',
                                      'data' => $StripeAccDetails], 200);
        }else{
            return response()->json(['error'=>'given user_id does not match with login user_id'], 201);
        }
    }

    public function getStripeAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'  => 'required',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $StripeAccount = StripeAccountDetails::where(['user_id' => $request->user_id])->get();
        
        if(count($StripeAccount) > 0){
            return response()->json(['success'=>true,'message'=>'Stripe Account Details','data'=> $StripeAccount], 200);
        }else{
            return response()->json(['success'=>false,'message'=> 'No Stripe Account found'], 201);
        }
    }

    public function setPublishedStatus(Request $request)
    {
        $user = auth()->user();

        $user->is_published = '1';
        $status = $user->save();
        if($status){
            return response()->json(['success'=>true,'message'=>'Vendor Published Successfully'], 200);
        }else{
            return response()->json(['success'=> false, 'message' => 'something went worng! try again'], 201);
        }
    }

    public function getDocument(Request $request){

        $validator = Validator::make($request->all(), [
            'doc_name'  => 'required|string',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $document = DocumentText::where(['doc_name' => $request->doc_name])->first();

        if($document){
            return response()->json(['success'=> true, 'data' => $document->content ], 200);
        }else{
            return response()->json(['success'=> false, 'message' => 'Please enter correct document name / Document not found!' ], 200);
        }
    }

    public function getVendorByCategoryId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id'  => 'required|integer',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $category = Category::find($request->category_id);
        if($category != null){
            $users = $category->users()->where('is_published', '1')->get();

            if(count($users) > 0){
                return response()->json(['success'=> true, 'data' =>$users ], 200);
            }else{
                return response()->json(['success'=> false, 'message' =>'No User Found!' ], 200);
            }
        }else{
            return response()->json(['success'=> false, 'message' =>'No Category Found!' ], 200);
        }
    }

    public function getVendorReviews(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vendor_id'  => 'required|integer',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $reviews = VendorReview::where('vendor_id',$request->vendor_id)->get();

        if(count($reviews) > 0){
            return response()->json(['success'=> true, 'data' =>$reviews ], 200);
        }else{
            return response()->json(['success'=> false, 'message' =>'No review Found!' ], 200);
        }
    }


    public function createReviews(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'vendor_id'  => 'required|integer',
            'text'  => 'required|string',
            'rating'  => 'required|integer|between:1,5',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        if($user->role == 'user')
        {
            $user_review = VendorReview::create(array_merge(
                $validator->validated(),
                [   'username'  => $user->name,
                    'user_pic'  => $user->profile_image,
                ]
            ));
    
            if($user_review){
                return response()->json(['success'=> true, 'data' =>$user_review ], 200);
            }else{
                return response()->json(['success'=> false, 'message' =>'Something went worng!' ], 200);
            }
        }else{
            return response()->json(['success'=> false, 'message' =>'Pass the user access token' ], 200);
        }
    }
}
