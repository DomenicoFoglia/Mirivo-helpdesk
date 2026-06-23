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


    // Refactoring: Gli attachment non sono piu' collegati direttamente ai Ticket quindi questo metodo non puo'
    // piu' funzionare. 
    // Si potrebbe fare 'hasManyThrough' e riuscire a collegare i Ticket direttamente agli attachemnt tramite 
    // Message ma per ora non ci serve
    // public function attachments(){
        // Attachments are related to tickets via messages, so use hasManyThrough
    //     return $this->hasManyThrough(Attachment::class, Message::class);
    // }
}
