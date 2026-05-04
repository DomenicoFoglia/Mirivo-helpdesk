<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id', 'email', 'role', 'token', 'accepted_at', 'expires_at'])]

class Invitation extends Model
{
    public function company(){
        return $this->belongsTo(Company::class);
    }
}
