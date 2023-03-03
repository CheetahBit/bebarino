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
    }

    public function checkRequirment()
    {
        $this->hasTicket = isset($this->ticket) ? "✅" : "❌";
        $this->hasPassport = isset($this->user->identity->passport) ? "✅" : "❌";
        $this->hasContact = $this->user->contact->isFullFill() ? "✅" : "❌";
    }
}
