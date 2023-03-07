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
        return $this->belongsTo(User::class, 'userId');
    }

    public function existsOrStore($data)
    {
        $from = [
            "country" => $data->fromCountry,
            "city" => $data->fromCity,
            "address" => $data->fromAddress,
        ];

        $to = [
            "country" => $data->fromCountry,
            "city" => $data->fromCity,
            "address" => $data->fromAddress,
        ];

        $address = Address::where($from);
        if ($address->doesntExist()) Address::create($from)->save();

        $address = Address::where($to);
        if ($address->doesntExist()) Address::create($to)->save();
    }
}
