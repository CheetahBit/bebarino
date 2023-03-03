<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    
    public function user()
    {
        return $this->belongsTo(User::class,'userId');
    }
}
