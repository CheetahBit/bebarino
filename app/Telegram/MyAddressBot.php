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

        if (isset($message->message_id)) {
            $messageId = $message->message_id - 1;
            $this->api->chat($userId)->updateButton()->messageId($messageId)->exec();
        }

        $this->api->chat($userId)->sendMessage()->text('myAddresses')->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('createAddress', 'data', 'MyAddress.create');
            $m->button('indexAddress', 'query', time())->inlineMode('addresses');
        })->exec();

        $action = config('telegram')->actions->myAddressesShow;
        $this->api->putCache($userId, 'action', $action);
    }

    public function show($message)
    {
        $userId = $message->from->id;
        $id = $message->text ?? $message->cache->address;

        $messageId = $message->message_id ?? ($message->message->message_id ?? 0) + 1;
        if ($messageId > 0)  $this->api->chat($userId)->updateButton()->messageId($messageId - 1)->exec();

        $address = User::find($userId)->addresses()->find($id);

        $this->api->chat($userId)->sendMessage()->text('addressInfo', $address)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('delete', 'data', 'MyAddress.delete');
            $m->button('edit', 'data', 'MyAddress.edit');
            $m->button('backward', 'data', 'MyAddress.backward');
        })->exec();

        $this->api->putCache($userId, 'address', $id);
    }

    public function edit($callback)
    {
        $userId = $callback->from->id;
        $messageId = $callback->message->message_id;

        $this->api->chat($userId)->updateButton()->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('backward', 'data', 'MyAddress.show');
        })->exec();

        $flow = new FlowBot();
        $flow->start($userId, 'address', 'MyAddress', 'update', 'index');
    }

    public function update($result)
    {
        $userId = $result->userId;
        $data = $result->data;
        $cache = $this->api->getCache($userId);
        User::find($userId)->addresses()->find($cache->address)->update((array)$data);

        $message = new stdClass;
        $message->from = new stdClass;
        $message->from->id = $userId;
        $message->cache = $cache;
        $this->show($message);
    }

    public function create($callback)
    {
        $userId = $callback->from->id;
        $cache = $callback->cache;
        $messageId = $callback->message_id ?? $callback->message->message_id;

        $this->api->chat($userId)->updateButton()->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('backward', 'data', 'MyAddress.backward');
        })->exec();

        if (isset($cache->flow)) $this->api->putCache($userId, 'history', $cache->flow);

        $flow = new FlowBot();
        $flow->start($userId, 'address', 'MyAddress', 'store', 'index');
    }

    public function store($result)
    {
        $userId = $result->userId;
        $data = $result->data;

        $address = User::find($userId)->addresses()->create((array)$data);
        $id = $address->id;
        $address->save();

        $cache = $this->api->getCache($userId);
        if (isset($cache->history)) {
            $this->api->putCache($userId, 'flow', $cache->history);
            $message = new stdClass;
            $message->from = new stdClass;
            $message->from->id = $userId;
            $message->message_id = 0;
            $message->text = $id;
            $cache->flow = $cache->history;
            $message->cache = $cache;
        } else {
            $this->api->chat($userId)->sendMessage()->text('saveSuccessfully')->exec();
            $message = new stdClass;
            $message->from = new stdClass;
            $message->from->id = $userId;
            $message->text = $id;
            $this->show($message);
        }
    }

    public function delete($callback)
    {
        $userId = $callback->from->id;
        $id = $callback->cache->address;
        $messageId = $callback->message->message_id;
        $text = $callback->message->text;
        $text .= "\n\n" . config('telegram')->messages->deleted;

        $this->api->chat($userId)->updateMessage()->text(plain: $text)->messageId($messageId)->exec();

        User::find($userId)->addresses()->find($id)->delete();

        $this->index($callback);
    }

    public function backward($callback)
    {
        $userId = $callback->from->id;
        $messageId = $callback->message->message_id;

        $this->api->chat($userId)->sendMessage()->text('cancelEdit')->keyboard()->rowKeys(function ($m) {
            $m->key('backward');
        })->exec();

        $this->api->chat($userId)->updateButton()->messageId($messageId)->exec();
        $this->index($callback);
    }
}
