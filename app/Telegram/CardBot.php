<?php

namespace App\Telegram;

class CardBot extends ParentBot
{

    public function index()
    {
        $this->clear();
        
        $this->messageId += $this->type == 'message' ? -1 : 0;
        $this->api->updateButton()->messageId($this->messageId)->exec();

        $this->api->sendMessage()->text('cards')->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('cards', 'query', time());
        })->exec();

        $this->putCache('action', 'showCard');
        $this->putCache('inline', 'cards');
    }

    public function show()
    {
        $data = explode('-', $this->data);
        $this->update->data = $data[1];
        match ($data[0]) {
            "showPackage" => (new PackageBot($this->update))->show(),
            "showTrip" => (new TripBot($this->update))->show()
        };
        $this->api->updateButton()->messageId($this->messageId - 1)->exec();
    }
}
