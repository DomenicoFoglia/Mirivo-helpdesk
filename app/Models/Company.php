<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'logo', 'ai_provider', 'ai_key', 'theme_color'])]
#[Hidden(['ai_provider', 'ai_key'])]

class Company extends Model
{
    public function users(){
        return $this->hasMany(User::class);
    }

    public function categories(){
        return $this->hasMany(Category::class);
    }

    public function tickets(){
        return $this->hasMany(Ticket::class);
    }

    public function faqs(){
        return $this->hasMany(Faq::class);
    }

    public function tags(){
        return $this->hasMany(Tag::class);
    }

    public function invitations(){
        return $this->hasMany(Invitation::class);
    }
}