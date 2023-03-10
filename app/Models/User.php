<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;


class User extends Model
{
    protected $fillable = [
        'id',
        'phone',
    ];

    public function account()
    {
        return $this->hasOne(Account::class,'userId');
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
