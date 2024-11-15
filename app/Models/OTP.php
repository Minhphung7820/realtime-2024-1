<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OTP extends Model
{
  protected $table = 'auth_otps';

  protected $guarded = ['id'];
  protected $fillable = [
    'email',
    'otp',
    'expired_at',
    'created_at',
    'updated_at',
    'user_id'
  ];
}
