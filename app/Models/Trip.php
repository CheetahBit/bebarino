<?php

namespace App\Models;

use App\Casts\StatusLabel;
use App\Casts\TicketImg;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        "status"
    ];

    public $hidden = [
        'id',
        'userId',
        'created_at',
    ];

    protected $casts = [
        'ticket' => TicketImg::class,
        "status" => StatusLabel::class
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function hasTicket(): Attribute
    {
        return Attribute::make(
            get: function () {
                $ticket = $this->getRawOriginal('ticket');
                return $ticket !== null ? "✅" : "❌";
            },
        );
    }

    public function hasPassport(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->user->account->hasPassport(),
        );
    }

    public function hasContact(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->user->account->hasContact(),
        );
    }



    protected function code(): Attribute
    {
        return Attribute::make(
            get: fn () => "#T" . $this->id + 1000,
        );
    }
}
