<?php

namespace App\Models;

use App\Casts\TicketImg;
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
        "desc",
        "messageId",
    ];

    public $hidden = [
        'id',
        'userId',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'ticket' => TicketImg::class,
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
        $ticket = $this->getRawOriginal('ticket');
        $this->hasTicket = $ticket !== null ? "✅" : "❌";
        $passport = $this->user->identity->getRawOriginal('passport');
        $this->hasPassport =  $passport !== null ? "✅" : "❌";
        $this->hasContact = $this->user->contact->isFullFill() ? "✅" : "❌";
    }
}
