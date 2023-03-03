<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'email',
        'country',
        'city',
        'address',
    ];

    public $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'phone' => NotEntered::class,
        'email' => NotEntered::class,
        'country' => NotEntered::class,
        'city' => PassportImg::class,
        'address' => PassportImg::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function isFullFill()
    {
        foreach ($this->fillable as $column) {
            if (!isset($this->{$column})) {
                return false;
            }
        }
        return true;
    }
}
