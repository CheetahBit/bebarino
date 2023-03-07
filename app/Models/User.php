<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    protected $fillable = [
        'id',
        'phone',
    ];

    public function identity()
    {
        return $this->hasOne(AccountIdentity::class,'userId');
    }

    public function bank()
    {
        return $this->hasOne(AccountBank::class,'userId');
    }

    public function contact()
    {
        return $this->hasOne(AccountContact::class,'userId');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class,'userId');
    }

    public function packages()
    {
        return $this->hasMany(Package::class,'userId');
    }


    public function trips()
    {
        return $this->hasMany(Trip::class,'userId');
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class,'userId');
    }

}
