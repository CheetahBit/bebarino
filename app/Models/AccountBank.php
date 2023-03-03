<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountBank extends Model
{
    use HasFactory;

    protected $fillable = [
        "country",
        "accountName",
        "accountNumber",
    ];

    public $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'country' => NotEntered::class,
        'accountName' => PassportImg::class,
        'accountNumber' => PassportImg::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
