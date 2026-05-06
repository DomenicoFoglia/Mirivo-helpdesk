<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'surname', 'email', 'password', 'role', 'company_id', 'level'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function assigneeTickets(){
        return $this->hasMany(Ticket::class, 'assignee_id');
    }

    public function userTickets(){
        return $this->hasMany(Ticket::class, 'user_id');
    }

    public function messages(){
        return $this->hasMany(Message::class);
    }

    public function attachments(){
        return $this->hasMany(Attachment::class);
    }

    public function company(){
        return $this->belongsTo(Company::class);
    }
}
