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
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function requirement()
    {
        $this->hasPassport = $this->user->identity->hasPassport();
        $this->hasContact = $this->user->contact->hasContact();
    }
}
