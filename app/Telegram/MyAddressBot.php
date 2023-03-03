<?php

namespace App\Telegram;

use App\Models\User;
use stdClass;

class MyAddressBot
{

    public $api;

    public function __construct()
    {
        $this->api = new APIBot();
    }

    public function index($message)
    {
        $userId = $message->from->id;
        $cache = $message->cache;

        $this->api->chat($userId)->sendMessage()->text('myAddresses')->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('createAddress', 'data', 'MyAddress.create');
            $m->button('indexAddress', 'query', time())->inlineMode('addresses');
        })->exec();

        $cache->action = config('telegram')->actions->myAddressesShow;
        $this->api->setCache($userId, $cache);
    }

    public function show($message)
    {
        $userId = $message->from->id;
        $cache = $message->cache;
        $id = $message->text;

        $address = User::find($userId)->addresses()->find($id);

        $this->api->chat($userId)->sendMessage()->text('addressInfo', $address)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('delete', 'data', 'Address.delete');
            $m->button('edit', 'data', 'Address.edit');
            $m->button('backward', 'data', 'Address.main');
        })->exec();

        $cache->id = $id;
        $this->api->setCache($userId, $cache);
    }

    public function edit($callback)
    {
        $userId = $callback->from->id;
        $cache = $callback->cache;
        $cache->id = $callback->data;

        $flow = new FlowBot();
        $flow->start($userId, 'address', 'Address', 'update', 'main');

        $this->api->setCache($userId, $cache);
    }

    public function update($data)
    {
        $userId = $data->userId;
        $cache = $this->api->getCache($userId);
        User::find($userId)->addresses()->find($cache->id)->update($data);

        $message = new stdClass;
        $message->form->id = $userId;
        $message->text = $cache->id;
        $message->cache = $cache;
        $this->show($message);
    }

    public function create($callback)
    {
        $userId = $callback->from->id;
        $flow = new FlowBot();
        $flow->start($userId, 'address', 'Address', 'store', 'index');
    }

    public function store($result)
    {
        $userId = $result->userId;
        $data = $result->data;

        $address = User::find($userId)->addresses()->create((array)$data);
        $id = $address->id;
        $address->save();

        $this->api->chat($userId)->sendMessage()->text('saveSuccessfully')->exec();

        $message = new stdClass;
        $message->form->id = $userId;
        $message->text = $id;
        $message->cache = $this->api->getCache($userId);
        $this->show($message);
    }

    public function delete($callback)
    {
        $userId = $callback->from->id;
        $id = $callback->data;
        User::find($userId)->addresses()->find($id)->delete();

        $this->index($callback);
    }
}
