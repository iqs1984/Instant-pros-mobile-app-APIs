<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Models\User;
use App\Models\VendorServices;
use App\Models\Category;
use App\Models\VendorSlot;

class OrderController extends Controller
{
    public function createOrder(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'user_id'  => 'required|integer',
            'vendor_id'  => 'required|integer',
            'service_id'  => 'required|integer',
            'slot_id'  => 'required|integer',
            'amount'  => 'required|integer',
            'address'  => 'required|String',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        if($user->role == 'user' && $user->id == $request->user_id)
        {
            $createOrder = Order::create($validator->validated());
            $slotDetails = VendorSlot::where('id' , $request->slot_id)->first();

            if($createOrder){
                $slotDetails->status = '1';
                $slotDetails->save();
                return response()->json(['success'=> true, 'message' => 'Order Created successfully','order_id' =>$createOrder->id ], 200);
            }else{
                return response()->json(['success'=> false, 'message' =>'Something went wrong!' ], 200);
            }
            
        }else{
            return response()->json(['success'=> false, 'message' =>'token and user_id mismatched' ], 200);
        }
    }

   
    public function getOrderDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id'  => 'required|integer',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $orderDetails = Order::with(['user', 'vendor', 'service', 'slot'])->find($request->order_id);

        if ($orderDetails) 
        {
            $order = [
                'order_id' => $orderDetails->id,
                'user_id' => $orderDetails->user_id,
                'vendor_id' => $orderDetails->vendor_id,
                'service_id' => $orderDetails->service_id,
                'slot_id' => $orderDetails->slot_id,
                'amount' => $orderDetails->amount,
                'address' => $orderDetails->address,
                'payment_id' => $orderDetails->payment_id,
                'order_status' => $orderDetails->order_status,
                'created_at' => date($orderDetails->created_at),
                'updated_at' => date($orderDetails->updated_at),
            ];

            $user = $orderDetails->user->only(['id', 'name', 'email', 'role','phone','profile_image','address','chatUserId']);
            $vendor = $orderDetails->vendor->only(['id', 'business_name', 'email','role', 'category_id','category_name','profile_image','phone','country_id','country_name','state_id','state_name','city_id','city_name','address','zip_code','chatUserId','is_published']);
            $service = $orderDetails->service->only(['id', 'vendor_id', 'title', 'price','duration','image','status']);
            $slot = $orderDetails->slot->only(['id', 'vendor_id', 'date', 'start_time','end_time','status']);

            return response()->json(['success'=> true,'order' =>$order, 'user' =>$user, 'vendor' =>$vendor, 'service' =>$service, 'slot' =>$slot  ], 200);

        } else {
            return response()->json(['success'=> false, 'message' =>'Order not found!' ], 200);
        }
        
    }


    public function orderUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id'  => 'required|integer',
            'order_status'  => 'required|integer|between:2,7',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $orderDetails = Order::where('id',$request->order_id)->first();

        if($orderDetails){

            $status_name = "";
            switch ($request->order_status) {
                case 2:
                    $status_name = 'Order accepted successfully';
                    break;
                case 3:
                    $status_name = 'Order cancelled successfully';
                    break;
                case 4:
                    $status_name = 'payment done successfully';
                    break;
                case 5:
                    $status_name = 'Order in progress';
                    break;
                case 6:
                    $status_name = 'Order Finished successfully';
                    break;
                case 7:
                    $status_name = 'Feedback updated successfully';
                    break;
            }
            
            $orderDetails->order_status = $request->order_status;
            $success = $orderDetails->save();
            if($success){
                return response()->json(['success'=> true, 'message' =>$status_name ], 200);
            }else{
                return response()->json(['success'=> false, 'message' =>'something went wrong!' ], 200);
            }
        }else{
            return response()->json(['success'=> false, 'message' =>'Order not found!' ], 200);
        }
    }

    public function getOrderByStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_status' => 'required|string|in:upcoming,cancelled,completed'
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }


        if($request->order_status == 'upcoming'){
            $orderStatus = '1';
        }elseif($request->order_status == 'cancelled'){
            $orderStatus = '3';
        }elseif($request->order_status == 'completed'){
            $orderStatus = '6';
        }

        $orderDetails = Order::with(['user', 'vendor', 'service', 'slot'])->where('order_status', $orderStatus)->get();

        if($orderDetails)
        {
            $jsonData = array();

            foreach($orderDetails as $data) 
            {
                $order = [
                    'order_id' => $data->id,
                    'user_id' => $data->user_id,
                    'vendor_id' => $data->vendor_id,
                    'service_id' => $data->service_id,
                    'slot_id' => $data->slot_id,
                    'amount' => $data->amount,
                    'address' => $data->address,
                    'payment_id' => $data->payment_id,
                    'order_status' => $data->order_status,
                    'created_at' => date($data->created_at),
                    'updated_at' => date($data->updated_at),
                ];
    
                $user = $data->user->only(['id', 'name', 'email', 'role','phone','profile_image','address','chatUserId']);
                $vendor = $data->vendor->only(['id', 'business_name', 'email','role', 'category_id','category_name','profile_image','phone','country_id','country_name','state_id','state_name','city_id','city_name','address','zip_code','chatUserId','is_published']);
                $service = $data->service->only(['id', 'vendor_id', 'title', 'price','duration','image','status']);
                $slot = $data->slot->only(['id', 'vendor_id', 'date', 'start_time','end_time','status']);

                $jsonData[] = ['order' =>$order, 'user' =>$user, 'vendor' =>$vendor, 'service' =>$service, 'slot' =>$slot];
            }
            return response()->json(['success'=> true,'data' =>$jsonData], 200);

        }else {
            return response()->json(['success'=> false, 'message' =>'Order not found!' ], 200);
        }
    }

    public function myBooking(Request $request)
    {
        $user = auth()->user();
        $perPage = 5;

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'page' => 'required|integer'
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        if($user->id == $request->user_id)
        {
            $allOrders = $user->orders()->paginate($perPage);

            $data = array();
    
            foreach($allOrders as $order){
    
                $service = VendorServices::where('id',$order->service_id)->first();
                
                $data[] = [
                    'order_id' => $order->id,
                    'title' => $service->title,
                    'price' => $service->price,
                    'duration' => $service->duration,
                    'image' => $service->image,
                ];
            }
    
            return response()->json(['success'=> true,'data' => $data], 200);
        }else{
            return response()->json(['success'=> false,'data' =>'user_id does not matched with login_user'], 200);
        }
    }

    public function orderReschedule(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'order_id'      => 'required|integer',
            'new_slot_id'   => 'required|integer',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $orderDetails = Order::where('id',$request->order_id)->first();
        $oldSlotDetails = VendorSlot::where('id',$orderDetails->slot_id)->first();
        $newSlotDetails = VendorSlot::where(['id' => $request->new_slot_id, 'status' => '0'])->first();

        if($orderDetails)
        {
            $orderDetails->slot_id = $request->new_slot_id;
            $success = $orderDetails->save();
            if($success == true)
            {
                $newSlotDetails->status = '1';
                $new_slot_status = $newSlotDetails->save();

                if($new_slot_status == true)
                {
                    $oldSlotDetails->status = '0';
                    $old_slot_status = $oldSlotDetails->save();

                    if($old_slot_status == true)
                    {
                        return response()->json(['success'=> true,'data' =>'Order reschedule successfully'], 200);

                    }else{

                        return response()->json(['success'=> false,'data' =>'new slot updated but old slot status not updated'], 200);
                    }
                }else{

                    return response()->json(['success'=> false,'data' =>'new slot status not updated'], 200);
                }
            }else{

                return response()->json(['success'=> false,'data' =>'order slot updated but old and new slot status not updated'], 200);
            }
        }else{

            return response()->json(['success'=> false,'data' =>'Order not found!'], 200);
        }
    }
}
