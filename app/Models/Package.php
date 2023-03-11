<?php

namespace App\Models;

use App\Casts\StatusLabel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        "fromCountry",
        "fromCity",
        "fromAddress",
        "toCountry",
        "toCity",
        "toAddress",
        "desc",
        "messageId",
        "status"
    ];

    protected $casts = [
        "status" => StatusLabel::class
    ];

    public $hidden = [
        'id',
        'userId',
        'created_at',
        'updated_at',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function getHasPassport()
    {
        return $this->user->account->hasPassport();  
    }

    public function getHasContact()
    {
        return $this->user->account->hasContact();  
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            get: fn () => "#P" . $this->id + 1000,
        );
    }
}
