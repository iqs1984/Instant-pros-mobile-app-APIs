<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordRest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'password_reset_tokens';
    protected $primaryKey = 'email';
    public $incrementing = false;

}
