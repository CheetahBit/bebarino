<?php

namespace App\Telegram;

use App\Models\Address;
use App\Models\User;
use stdClass;

class AddressBot extends ParentBot
{

    public function index()
    {
        $this->clear();
        
        if ($this->type == 'message')
            $this->api->updateButton()->messageId($this->messageId - 1)->exec();

        $this->api->sendMessage()->text('addresses')->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('createAddress', 'data', 'Address.create');
            $m->button('addresses', 'query', time());
        })->exec();

        $this->putCache('action', 'showAddress');
        $this->putCache('inline', 'addresses');
    }

    public function show()
    {
        $this->messageId += $this->type == 'message' ? -1 : 0;
        $this->api->updateButton()->messageId($this->messageId)->exec();

        $id = $this->data;
        $address = $this->user->addresses()->find($id);

        $this->api->sendMessage()->text('addressInfo', $address)->inlineKeyboard()->rowButtons(function ($m) use ($id) {
            $m->button('delete', 'data', 'Address.destroy.' . $id);
            $m->button('edit', 'data', 'Address.edit.' . $id);
            $m->button('backward', 'data', 'Address.backward');
        })->exec();
    }

    public function edit()
    {
        $id = $this->data;
        $this->api->updateButton()->inlineKeyboard()->rowButtons(function ($m) use ($id) {
            $m->button('backward', 'data', 'Address.show.' . $id);
        })->messageId($this->messageId)->exec();

        $this->putCache('address', $id);

        $flow = new FlowBot($this->update);
        $flow->start('address','update');
    } 

    public function update()
    {
        $address = $this->user->addresses()->find($this->cache->address);
        $address->update((array)$this->result->data);
        $this->show();
    }

    public function create()
    {
        $this->api->updateButton()->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('backward', 'data', 'Address.backward');
        })->messageId($this->messageId)->exec();

        $flow = new FlowBot($this->update);
        $flow->start('address','store');
    }

    public function store()
    {
        $address = $this->user->addresses()->firstOrCreate((array)$this->result->data);
        $id = $address->id;

        $this->api->sendMessage()->text('saveSuccessfully')->exec();
        $this->data = $id;
        $this->show();
    }

    public function destroy()
    {
        $this->user->addresses()->find($this->data)->delete();
        $this->api->sendMessage()->text('deleteSuccessfully')->exec();
        $this->api->updateMessage()->messageId($this->messageId)->exec();
        $this->index();
    }

    public function backward()
    {
        $this->api->updateButton()->messageId($this->messageId)->exec();
        $this->api->sendMessage()->text('removeKeyboard')->keyboard()->rowKeys(function ($m) {
            $m->key('backward');
        })->exec();
        $this->index();
    }

    static function storeFromToAddress($user, $data)
    {
        $from = [
            "country" => $data->fromCountry,
            "city" => $data->fromCity,
            "address" => $data->fromAddress,
        ];

        $to = [
            "country" => $data->toCountry,
            "city" => $data->toCity,
            "address" => $data->toAddress,
        ];

        $user->addresses()->firstOrCreate($from);
        $user->addresses()->firstOrCreate($to);
    }
}
