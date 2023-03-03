<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        "fromAddress",
        "toAddress",
        "desc"
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'userId');
    }

    public function fromCC()
    {
        return Attribute::make(
            get: fn () => implode(",", array_slice(explode(",", $this->fromAddress),0,2))
        );
    }

    public function toCC()
    {
        return Attribute::make(
            get: fn () => implode(",", array_slice(explode(",", $this->toAddress),0,2))
        );
    }
}
