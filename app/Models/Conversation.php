<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
  use HasFactory;

  protected $fillable = [
    'name',
    'type',
    'created_by'
  ];

  public function participants()
  {
    return $this->hasMany(ConversationParticipant::class);
  }

  public function messages()
  {
    return $this->hasMany(Message::class);
  }

  public function creator()
  {
    return $this->belongsTo(User::class, 'created_by');
  }
}
