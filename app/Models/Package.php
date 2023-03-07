<?php

namespace App\Models;

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
    ];

    public $hidden = [
        'id',
        'userId',
        'created_at',
        'updated_at'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function requirement()
    {
        $this->hasPassport = $this->user->idnetity()->hasPassport();
        $this->hasContact = $this->user->contact()->hasContact();
    }
}
