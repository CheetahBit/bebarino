<?php

namespace App\Models;

use App\Casts\NotEntered;
use App\Casts\PassportImg;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AccountIdentity extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'firstname',
        'lastname',
        'passport'
    ];

    public $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'username' => NotEntered::class,
        'firstname' => NotEntered::class,
        'lastname' => NotEntered::class,
        'passport' => PassportImg::class,
    ];

    
    public function user()
    {
        return $this->belongsTo(User::class,'userId');
    }

    public function hasPassport()
    {
        $passport = $this->getRawOriginal('passport');
        return $passport !== null ? "✅" : "❌";
    }
}
