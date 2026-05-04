<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id', 'name'])]

class Category extends Model
{
    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function tickets(){
        return $this->hasMany(Ticket::class);
    }

    public function faqs(){
        return $this->hasMany(Faq::class);
    }
}
