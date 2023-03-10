<?php

namespace App\Models;

use App\Casts\NotEntered;
use App\Casts\PassportImg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;
    protected $fillable = [
        'username',
        'fullname',
        'passport',
        'phone',
        'email',
        'country',
        'city',
        'address',
        "bankCountry",
        "accountName",
        "accountNumber",
    ];

    public $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'username' => NotEntered::class,
        'fullname' => NotEntered::class,
        'passport' => PassportImg::class,
        'phone' => NotEntered::class,
        'email' => NotEntered::class,
        'country' => NotEntered::class,
        'city' => NotEntered::class,
        'address' => NotEntered::class,
        'bankCountry' => NotEntered::class,
        'accountName' => NotEntered::class,
        'accountNumber' => NotEntered::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function hasPassport()
    {
        $passport = $this->getRawOriginal('passport');
        return $passport !== null ? "✅" : "❌";
    }

    public function hasContact()
    {
        $flag = true;
        foreach ($this->fillable as $column) {
            if ($this->getRawOriginal($column) == null) {
                $flag =  false;
            }
        }
        return $flag ? "✅" : "❌";
    }
}
