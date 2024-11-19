<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeenMessage extends Model
{
  use HasFactory;

  protected $table = 'seen_messages';

  protected $fillable = [
    'user_id',
    'message_id',
    'conversation_id',
    'created_at',
    'updated_at',
    'seen_at',
  ];


  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
