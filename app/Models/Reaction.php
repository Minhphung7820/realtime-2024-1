<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    protected $table = 'reactions';

    protected $fillable = [
        'id',
        'message_id',
        'emoji',
        'created_at',
        'updated_at',
        'user_id',
    ];
}
