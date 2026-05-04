<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id', 'name'])]

class Tag extends Model
{
    public function tickets(){
        return $this->belongsToMany(Ticket::class);
    }

    public function company(){
        return $this->belongsTo(Company::class);
    }
}
