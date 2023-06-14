<?php

namespace App\Models;
use App\Models\Country;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function city()
    {
        return $this->hasMany('App\Models\City');
    }

    public function country()
    {
        return $this->belongsTo('App\Models\Country');
    }
}
