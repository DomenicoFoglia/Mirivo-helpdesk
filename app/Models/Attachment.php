<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['message_id', 'user_id', 'filename', 'original_filename', 'path', 'mime_type', 'size'])]

class Attachment extends Model
{
    protected $casts = [
        'size' => 'integer',
    ];

    public function message(){
        return $this->belongsTo(Message::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}