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

        $address = $this->where($from);
        if ($address->doesntExist()) $this->create($from)->save();

        $address = $this->where($to);
        if ($address->doesntExist()) $this->create($to)->save();
    }
}
