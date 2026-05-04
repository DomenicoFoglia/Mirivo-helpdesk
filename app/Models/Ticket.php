<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id', 'user_id', 'assignee_id', 'category_id', 'title', 'status', 'priority', 'closed_at'])]

class Ticket extends Model
{
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(){
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function messages(){
        return $this->hasMany(Message::class);
    }

    public function tags(){
        return $this->belongsToMany(Tag::class);
    }

    public function attachments(){
        return $this->hasMany(Attachment::class);
    }
}
