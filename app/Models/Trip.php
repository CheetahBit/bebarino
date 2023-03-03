<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        "fromAddress",
        "toAddress",
        "date",
        "ticket",
        "weight",
        "price",
        "desc"
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function cc()
    {
        $this->fromAddress = implode(",", array_slice(explode(",", $this->fromAddress), 0, 2));
        $this->toAddress = implode(",", array_slice(explode(",", $this->toAddress), 0, 2));

        return $this;
    }

    public function hasTicket()
    {
        $this->hasTicket = isset($this->ticket) ? "✅" : "❌";
        return $this;
    }

    public function hasPassport()
    {
        $this->hasPassport = isset($this->user->identity->passport) ? "✅" : "❌";
        return $this;
    }

    public function hasContact()
    {
        $this->hasPassport = $this->user->contact->isFullFill() ? "✅" : "❌";
        return $this;
    }
}
