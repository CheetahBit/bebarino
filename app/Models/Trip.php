<?php

namespace App\Models;

use App\Casts\StatusLabel;
use App\Casts\TicketImg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        "fromCountry",
        "fromCity",
        "fromAddress",
        "toCountry",
        "toCity",
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
        "status" => StatusLabel::class
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function hasTicket()
    {
        $ticket = $this->ticket;
        return $ticket !== null ? "✅" : "❌";
    }


    public function requirement()
    {
        $this->hasTicket = $this->hasTicket();
        $this->hasPassport = $this->user->identity->hasPassport();
        $this->hasContact = $this->user->contact->hasContact();
    }
}
