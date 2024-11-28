<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'seen'
    ];

    protected $casts = ['content' => 'json'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class)
            ->selectRaw('message_id, emoji, COUNT(*) as count')
            ->orderBy('count', 'desc')
            ->groupBy('message_id', 'emoji');
    }
}
