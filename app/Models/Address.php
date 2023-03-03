<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'country',
        'city',
        'address',
    ];

    public $hidden = [
        'id',
        'userId',
        'created_at',
        'updated_at'
    ];

    

    public function user()
    {
        return $this->belongsTo(User::class,'userId');
    }
}
