<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        "package",
        "trip",
        "type",
        "status",
    ];

    public function package()
    {
        return $this->belongsTo(Package::class,'package');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class,'trip');
    }
}
