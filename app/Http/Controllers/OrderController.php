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
use App\Models\UserFcmTokens;
use App\Models\Notification;
use App\Models\EscowPaymentDetail;

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
            $vendor_name = User::where('id', $request->vendor_id)->first();
            $createOrder = Order::create($validator->validated());
            $slotDetails = VendorSlot::where('id' , $request->slot_id)->first();

            if($createOrder)
            {
                $slotDetails->status = '1';
                $slotDetails->save();

                if($request->escrow_transaction_id != null)
                {
                    $updateEscrowDetails = EscowPaymentDetail::create(
                        [   'order_id' => $createOrder->id,
                            'transaction_id' => $request->escrow_transaction_id,
                        ]);
                }

                $msg = 'Your booking is confirmed. Thank you for choosing '.$vendor_name->business_name.'. You will receive a confirmation email shortly.';

                $this->notificationCURL($user->id, $createOrder->vendor_id, $createOrder->id, $msg, $msg, $user->id, $createOrder->vendor_id);

                return response()->json(['success'=> true, 'message' => $msg ,'order_id' =>$createOrder->id ], 200);
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

        $orderDetails = Order::with(['user', 'vendor', 'service', 'slot','EscowPaymentDetail'])->find($request->order_id);

        if ($orderDetails) 
        {
            $order = [
                'order_id' => $orderDetails->id,
                'user_id' => $orderDetails->user_id,
                'vendor_id' => $orderDetails->vendor_id,
                'service_id' => $orderDetails->service_id,
                'slot_id' => $orderDetails->slot_id,
                'amount' => $orderDetails->amount,
                'disbursement_fee' => $orderDetails->disbursement_fee,
                'processing_fee' => $orderDetails->processing_fee,
                'address' => $orderDetails->address,
                'order_status' => $orderDetails->order_status,
                'accepted_at' => $orderDetails->accepted_at,
                'created_at' => date($orderDetails->created_at),
                'updated_at' => date($orderDetails->updated_at),
            ];

            $user = $orderDetails->user->only(['id', 'name', 'email', 'role','phone','profile_image','address','country_id','country_name','state_id','state_name','city_id','city_name','zip_code','chatUserId']);
            $vendor = $orderDetails->vendor->only(['id', 'business_name', 'email','role', 'category_id','category_name','profile_image','phone','country_id','country_name','state_id','state_name','city_id','city_name','address','zip_code','chatUserId','is_published']);
            $service = $orderDetails->service->only(['id', 'vendor_id', 'title', 'price','duration','image','status']);
            $slot = $orderDetails->slot->only(['id', 'vendor_id', 'date', 'start_time','end_time','status']);
            $PaymentDetail = $orderDetails->EscowPaymentDetail;

            return response()->json(['success'=> true,'order' =>$order, 'user' =>$user, 'vendor' =>$vendor, 'service' =>$service, 'slot' =>$slot, 'payment_detail' => $PaymentDetail], 200);

        } else {
            return response()->json(['success'=> false, 'message' =>'Order not found!' ], 200);
        }
        
    }

    public function orderUpdate(Request $request)
    {
        $login_user = auth()->user();
        $validator = Validator::make($request->all(), [
            'order_id'  => 'required|integer',
            'order_status'  => 'required|integer|between:2,8',
            'payment_object' => 'string',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $orderDetails = Order::where('id',$request->order_id)
        ->where(function ($query) use ($login_user){
            $query->where('user_id',$login_user->id)->orWhere('vendor_id',$login_user->id);
        })->first();

        $vendor_name = User::where('id', $orderDetails->vendor_id)->first();
        $user_name = User::where('id', $orderDetails->user_id)->first();

        if($orderDetails){

            $status_name = "";
            switch ($request->order_status) {
                case 2:
                    $status_name = $user_name->name." booking accepted! Please review the details and be prepared for the scheduled service. Thank you!";
                    $orderDetails->accepted_at = now();
                    $this->notificationCURL($orderDetails->vendor_id, $orderDetails->user_id, $orderDetails->id, $status_name, $status_name, $orderDetails->user_id, $orderDetails->vendor_id);
                    break;
                case 3:
                    $slotDetails = VendorSlot::where('id',$orderDetails->slot_id)->first();
                    $slotDetails->status = '0';
                    $slotDetails->save();
                    if($login_user->role == 'user'){
                        $status_name = "Order Cancelled by the customer";
                        $this->notificationCURL($orderDetails->user_id, $orderDetails->vendor_id, $orderDetails->id, $status_name, $status_name, $orderDetails->user_id, $orderDetails->vendor_id);
                    }else if($login_user->role == 'vendor'){
                        $status_name = 'Order Cancelled by the vendor';
                        $this->notificationCURL($orderDetails->vendor_id, $orderDetails->user_id, $orderDetails->id, $status_name, $status_name, $orderDetails->user_id, $orderDetails->vendor_id);
                    }
                    break;
                case 4:
                    // if($request->payment_object && payment_sucess == true){
                        

                    //     // convert string obejct to json 
                    //     // save object into database 
                    //     // update order status 
                    //     // update ppayment status


                    // }else{

                    // }

                    $status_name = 'Payment successful! Your booking is confirmed. Thank you for choosing '.$vendor_name->business_name.'. Receipt will be emailed.';
                    $this->notificationCURL($orderDetails->user_id, $orderDetails->vendor_id, $orderDetails->id, $status_name, $status_name, $orderDetails->user_id, $orderDetails->vendor_id);
                    break;
                case 5:
                    $status_name = 'Booking status updated to "In Progress". Please proceed with the scheduled service. Thank you!';
                    $this->notificationCURL($orderDetails->vendor_id, $orderDetails->user_id, $orderDetails->id, $status_name, $status_name, $orderDetails->user_id, $orderDetails->vendor_id);
                    break;
                case 6:
                    $status_name = 'Job completed successfully. Thank you for choosing our services. Please provide your feedback';
                    $this->notificationCURL($orderDetails->vendor_id, $orderDetails->user_id, $orderDetails->id, $status_name, $status_name, $orderDetails->user_id, $orderDetails->vendor_id);
                    break;
                case 7:
                    $status_name = 'Fund Released successfully';
                    $this->notificationCURL($orderDetails->user_id, $orderDetails->vendor_id, $orderDetails->id, $status_name, $status_name, $orderDetails->user_id, $orderDetails->vendor_id);
                    break;
                case 8:
                    $status_name = 'Thank you for your feedback! Your input is valuable in enhancing our services. We appreciate it.';
                    $this->notificationCURL($orderDetails->user_id, $orderDetails->vendor_id, $orderDetails->id, $status_name, $status_name, $orderDetails->user_id, $orderDetails->vendor_id);
                    break;
            }
            $orderDetails->order_status = $request->order_status;
            $success = $orderDetails->save();
            if($success){
                return response()->json(['success'=> true, 'message' =>$status_name, 'order_id' => $orderDetails->id ], 200);
            }else{
                return response()->json(['success'=> false, 'message' =>'something went wrong!' ], 200);
            }
        }else{
            return response()->json(['success'=> false, 'message' =>'Order not found!' ], 200);
        }
    }

    public function notificationCURL($sender_id, $receiver_id, $order_id, $title, $body, $user_id, $vendor_id)
    {
        $saveNotification = Notification::create(
            ['sender_id' =>$sender_id,
            'receiver_id' =>$receiver_id,
            'order_id' =>$order_id,
            'user_id' =>$user_id,
            'vendor_id' =>$vendor_id,
            'title' =>$title,
            'description' =>$body,
            ]);

        $fcm_token_list  = UserFcmTokens::where('user_id', $receiver_id)->get();
        $curl_handle = curl_init();
        
        $header = array(
            'Content-Type: application/json',
            'Authorization: key=AAAApM7ikzU:APA91bHnXVqRtJEBf_hTzANms6QUlhVIxJEtPYAMMuxxYRFYtWrigVy68gC4tJG6s_MzMxVsh4sv_bVLr-TNiiV39VUSUhZlGzFxjygw3tOIS7gDnOYCO76mH8cmheR8_CyIgIvN_Bkv'
        );
        
        foreach($fcm_token_list as $list)
        {
            $notify_details = array(
                'to' => $list->fcm_token,
                'notification' => array(
                    'title'             => $title, 
                    'body'              => $body, 
                    'mutable_content'   => true, 
                    'sound'             => 'Tri-tone'
                ), 
                'data' => array(
                    'user_id'       => $user_id, 
                    'vendor_id'     => $vendor_id,
                    'title'         => $title, 
                    'description'   => $body, 
                    'order_id'      => $order_id, 
                    'status'        => '0'
                )
            );

            $postField = json_encode($notify_details);

            curl_setopt($curl_handle, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handle, CURLOPT_ENCODING, '');
            curl_setopt($curl_handle, CURLOPT_MAXREDIRS, 10);
            curl_setopt($curl_handle, CURLOPT_TIMEOUT, 0);
            curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl_handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postField);
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $header);
    
            $response = curl_exec($curl_handle);
            curl_close($curl_handle);
        }
    }

    public function getOrderByStatus(Request $request)
    {
        $perPage = 20;
        $login_user = auth()->user();

        $validator = Validator::make($request->all(), [
            'order_status' => 'required|integer|in:0,3,9',
            'page' => 'required|integer|min:0'
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $forUpcoming = ['1', '2', '4', '5','6'];
        $forCompleted = ['7','8'];

        if($login_user->role == 'user')
        {
            $user = $login_user;
            if($request->order_status == 0)
            {
                $orderDetails = Order::with(['user', 'vendor', 'service', 'slot','EscowPaymentDetail'])->where('user_id', $user->id)->whereIn('order_status', $forUpcoming)->orderBy('updated_at', 'desc')->paginate($perPage);

            }elseif($request->order_status == 3)
            {
                $orderDetails = Order::with(['user', 'vendor', 'service', 'slot','EscowPaymentDetail'])->where(['user_id' => $user->id,'order_status' => '3'])->orderBy('updated_at', 'desc')->paginate($perPage);
                
            }elseif($request->order_status == 9)
            {
                $orderDetails = Order::with(['user', 'vendor', 'service', 'slot','review','EscowPaymentDetail'])->where('user_id', $user->id)->whereIn('order_status', $forCompleted)->orderBy('updated_at', 'desc')->paginate($perPage);
            }
            
        }else{
            $vendor = $login_user;

            if($request->order_status == 0)
            {
                $orderDetails = Order::with(['user', 'vendor', 'service', 'slot','EscowPaymentDetail'])->where('vendor_id', $vendor->id)->whereIn('order_status', $forUpcoming)->orderBy('updated_at', 'desc')->paginate($perPage);

            }elseif($request->order_status == 3)
            {
                $orderDetails = Order::with(['user', 'vendor', 'service', 'slot','EscowPaymentDetail'])->where(['vendor_id' => $vendor->id,'order_status' => '3'])->orderBy('updated_at', 'desc')->paginate($perPage);
                
            }elseif($request->order_status == 9)
            {
                $orderDetails = Order::with(['user', 'vendor', 'service', 'slot','review','EscowPaymentDetail'])->where('vendor_id', $vendor->id)->whereIn('order_status', $forCompleted)->orderBy('updated_at', 'desc')->paginate($perPage);
            }

        }

        switch ($request->order_status) {
            case 0:
                $error_msg = 'No upcoming order found!';
                break;
            case 3:
                $error_msg = 'No cancelled order found!';
                break;
            case 9:
                $error_msg = 'No completed order found!';
                break;
        }

        if(count($orderDetails) > 0)
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
                    'disbursement_fee' => $data->disbursement_fee,
                    'processing_fee' => $data->processing_fee,
                    'order_status' => $data->order_status,
                    'accepted_at' => $data->accepted_at,
                    'created_at' => date($data->created_at),
                    'updated_at' => date($data->updated_at),
                ];
    
                $user = $data->user->only(['id', 'name', 'email', 'role','phone','profile_image','address','country_id','country_name','state_id','state_name','city_id','city_name','zip_code','chatUserId']);
                $vendor = $data->vendor->only(['id', 'business_name', 'email','role', 'category_id','category_name','profile_image','phone','country_id','country_name','state_id','state_name','city_id','city_name','address','zip_code','chatUserId','is_published']);
                $service = $data->service->only(['id', 'vendor_id', 'title', 'price','duration','image','status']);
                $slot = $data->slot->only(['id', 'vendor_id', 'date', 'start_time','end_time','status']);
                $PaymentDetail = $data->EscowPaymentDetail;
                $review = null;
                if($data->review != null){
                    $review = $data->review->only(['id', 'vendor_id', 'user_id', 'order_id','service_id','username','user_pic','comment','rating']);
                }
                $jsonData[] = ['order' =>$order, 'user' =>$user, 'vendor' =>$vendor, 'service' =>$service, 'slot' =>$slot,'review' => $review,'PaymentDetail' => $PaymentDetail];
            }
            return response()->json(['success'=> true,'data' =>$jsonData], 200);

        }else {
            return response()->json(['success'=> false, 'message' => $error_msg], 200);
        }
    }

    public function myBooking(Request $request)
    {
        $user = auth()->user();
        $perPage = 20;

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
            $allOrders = $user->orders()->where('order_status','<>','3')->paginate($perPage);
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
        $vendor_name = User::where('id', $orderDetails->vendor_id)->first();
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
                        $msg = 'Your booking has been successfully rescheduled. Thank you for choosing '.$vendor_name->business_name.'. New details will follow.';
                        $this->notificationCURL($orderDetails->user_id, $orderDetails->vendor_id, $orderDetails->id, $msg, $msg, $orderDetails->user_id, $orderDetails->vendor_id);
                        return response()->json(['success'=> true,'data' => $msg,'order_id' => $orderDetails->id ], 200);

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


    public function getNotification(Request $request)
    {
        $perPage = 20;
        $login_user = auth()->user();

        $validator = Validator::make($request->all(), [
            'page' => 'required|integer|min:0',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $notificationList = Notification::with(['user', 'vendor'])->where('receiver_id', $login_user->id)->orderBy('created_at', 'desc')->paginate($perPage);

        if(count($notificationList) > 0)
        {
            $jsonData = array();

            foreach($notificationList as $data) 
            {
                if($data->status == '0')
                {
                    $data->status = '1';
                    $data->save();
                }
                
                $notificationData = [
                    'id' => $data->id,
                    'sender_id' => $data->sender_id,
                    'receiver_id' => $data->receiver_id,
                    'order_id' => $data->order_id,
                    'user_id' => $data->user_id,
                    'vendor_id' => $data->vendor_id,
                    'title' => $data->title,
                    'description' => $data->description,
                    'status' => $data->status,
                    'created_at' => date($data->created_at),
                    'updated_at' => date($data->updated_at),
                ];
    
                $user = $data->user->only(['id', 'name', 'email', 'role','phone','profile_image','address','country_id','country_name','state_id','state_name','city_id','city_name','zip_code','chatUserId']);
                $vendor = $data->vendor->only(['id', 'business_name', 'email','role', 'category_id','category_name','profile_image','phone','country_id','country_name','state_id','state_name','city_id','city_name','address','zip_code','chatUserId','is_published']);
                
                $jsonData[] = ['notification' =>$notificationData, 'user' =>$user, 'vendor' =>$vendor];
            }

            return response()->json(['success'=> true,'data' => $jsonData], 200);
        }else{
            return response()->json(['success'=> false,'data' =>'No notification found!'], 500);
        }
    }

    public function unreadNotification(Request $request)
    {
        $login_user = auth()->user();

        $notificationList = Notification::where(['receiver_id' => $login_user->id, 'status' => '0'])->count();
        
        if($notificationList > 0){
            return response()->json(['success'=> true, 'data' => $notificationList ], 200);
        }else{
            return response()->json(['success'=> false, 'data' => $notificationList ], 200);
        }
    }

    public function deleteNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_id'  => 'required|integer',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $deleteNotification = Notification::where('id', $request->notification_id)->delete();

        if($deleteNotification == true){
            return response()->json(['success'=> true, 'message' =>'Notification deleted successfully' ], 200);
        }else{
            return response()->json(['success'=> false, 'message' =>'Notification not found!' ], 400);
        }
    }

    public function updateProcessingFee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id'  => 'required|integer',
            'processing_fee'  => 'required|Decimal:0,2',
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $orderDetails = Order::where('id',$request->order_id)->first();

        if($orderDetails){
            $orderDetails->processing_fee = $request->processing_fee;
            $orderDetails->save();

            return response()->json(['success'=> true, 'message' =>'processing fee updated successfully' ], 200);
        }else{
            return response()->json(['success'=> false, 'message' =>'Order not found!' ], 400);
        }
    }

    
    public function myTransaction(Request $request)
    {
        $login_user = auth()->user();
        $perPage = 20;

        $validator = Validator::make($request->all(), [
            'from_date'                 => 'date_format:Y-m-d',
            'to_date'                   => 'date_format:Y-m-d|after:from_date',
            'payment_status_pending'    => 'string|in:0',
            'payment_status_escrow'     => 'string|in:1',
            'payment_status_paid'       => 'string|in:2',
            'username'                  => 'string',
            'page'                      => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $validator->messages()->toJson();
        }

        if($login_user->role == 'user')
        {
            $allOrders = Order::where('user_id', $login_user->id)->where('order_status', '>=', '4')->orderBy('created_at', 'desc');
            $count_all_orders = $allOrders->get();

            if (count($count_all_orders) > 0) 
            {
                $totalSpend = $allOrders->sum('amount');
                $orderList = $allOrders;

                if ($request->from_date != '' && $request->to_date != '') {
                    $orderList = $orderList->whereDate('created_at', '>=', $request->from_date)->whereDate('created_at', '<=', $request->to_date);
                }

                if ($request->payment_status_pending != '' || $request->payment_status_escrow != '' || $request->payment_status_paid != '' ) 
                {
                    $orderList = $orderList->where(function ($query) use ($request) {
                        $query->where('payment_status', $request->payment_status_pending)
                            ->orWhere('payment_status', $request->payment_status_escrow)
                            ->orWhere('payment_status', $request->payment_status_paid);
                    });
                }

                if ($request->username != '') 
                {
                    $vendor_id_arr = User::where('business_name', 'LIKE', '%' . $request->username . '%')->pluck('id')->toArray();
                    $orderList = $orderList->whereIn('vendor_id', $vendor_id_arr);
                }

                $orderListArr = $orderList->get();
                $newOrderList = array();

                foreach($orderListArr as $order)
                {
                    $username = User::where('id', $order->vendor_id)->first();
                    $newOrderList[] = array_merge($order->toArray(),['vendor_name' => $username->business_name]); 
                }

                return response()->json(['success' => true, 'data' => $newOrderList, 'total_spend' => $totalSpend], 200);

            } else {
                return response()->json(['success' => false, 'message' => 'no transaction found'], 200);
            }

        } else{
            $allOrders = Order::where('vendor_id', $login_user->id)->where('order_status', '>=', '4')->orderBy('created_at', 'desc');
            $count_all_orders = $allOrders->get();

            if (count($count_all_orders) > 0) 
            {
                $totalSpend = $allOrders->sum('amount');
                $orderList = $allOrders;
    
                if ($request->from_date != '' && $request->to_date != '') {
                    $orderList = $orderList->whereDate('created_at', '>=', $request->from_date)->whereDate('created_at', '<=', $request->to_date);
                }
    
                if ($request->payment_status_pending != '' || $request->payment_status_escrow != '' || $request->payment_status_paid != '' ) 
                {
                    $orderList = $orderList->where(function ($query) use ($request) {
                        $query->where('payment_status', $request->payment_status_pending)
                            ->orWhere('payment_status', $request->payment_status_escrow)
                            ->orWhere('payment_status', $request->payment_status_paid);
                    });
                }
    
                if ($request->username != '') 
                {
                    $user_id_arr = User::where('name', 'LIKE', '%' . $request->username . '%')->pluck('id')->toArray();
                    $orderList = $orderList->whereIn('user_id', $user_id_arr);
                }
    
                $orderListArr = $orderList->get();
                if($orderListArr->count() > 0)
                {
                    $newOrderList = array();

                    foreach($orderListArr as $order)
                    {
                        $username = User::where('id', $order->user_id)->first();
                        $newOrderList[] = array_merge($order->toArray(),['user_name' => $username->name]); 
                    }

                    return response()->json(['success' => true, 'data' => $newOrderList, 'total_earning' => $totalSpend], 200);

                }else{
                    return response()->json(['success' => false, 'message' => 'please enter correct keyword'], 200);
                }
            } else {
                return response()->json(['success' => false, 'message' => 'no transaction found'], 200);
            }
        }
    }

    public function updatePaymentStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'payment_status' => 'required|string|in:1,2'
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $orderDetails = Order::where('id', $request->order_id)->first();

        if($orderDetails){
            $orderDetails->payment_status = $request->payment_status;
            $orderDetails->save();
            return response()->json(['success'=> true, 'message' =>'payment status updated successfully' ], 200);
        }
        
        return response()->json(['success'=> false, 'message' =>'No order found' ], 200);
    }

    public function paymentSuccessURL(Request $request)
    {        
        return response()->json(['success'=> true, 'response' => $request->input()], 200);
    }

    public function updateEscrowTransctionID(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'escrow_transaction_id' => 'required'
        ]);

        if($validator->fails())
        {
            return $validator->messages()->toJson();
        }

        $escrowDetails = EscowPaymentDetail::where('order_id', $request->order_id)->first();

        if($escrowDetails){
            $escrowDetails->transaction_id = $request->escrow_transaction_id;
            $escrowDetails->save();
            return response()->json(['success'=> true, 'message' =>'transaction id updated successfully' ], 200);
        }else{
            
            $createEscrowDetails = EscowPaymentDetail::create([
                'order_id' => $request->order_id,
                'transaction_id' => $request->escrow_transaction_id,
            ]);
            
            return response()->json(['success'=> true, 'message' =>'transaction id added successfully' ], 200);
        }
    }

}
