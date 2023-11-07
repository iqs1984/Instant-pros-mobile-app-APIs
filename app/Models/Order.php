<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\VendorServices;
use App\Models\VendorSlot;
use App\Models\VendorReview;
use App\Models\EscowPaymentDetail;

class Order extends Model
{
    use HasFactory;

    protected  $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id', 'id');
    }

    public function service()
    {
        return $this->belongsTo(VendorServices::class, 'service_id', 'id');
    }

    public function slot()
    {
        return $this->belongsTo(VendorSlot::class, 'slot_id', 'id');
    }

    public function review()
    {
        return $this->belongsTo(VendorReview::class, 'review_id', 'id');
    }

    public function EscowPaymentDetail()
    {
        return $this->belongsTo(EscowPaymentDetail::class,'id','order_id');
    }
}
