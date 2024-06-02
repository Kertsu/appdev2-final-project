<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'initiator_id', 'recipient_id', 'content', 'initiator_username'
    ];

    public function initiator(){
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function recipient(){
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function messages(){
        return $this->hasMany(Message::class);
    }

    public function latestMessage(){
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
