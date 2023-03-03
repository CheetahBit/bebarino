<?php

namespace App\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use stdClass;

class AccountBot
{

    public $api;
    public $config;

    public function __construct()
    {
        $this->api = new APIBot();
        $this->config = config('telegram');
    }

    public function index($message)
    {
        $userId = $message->from->id;
        $this->api->deleteCache($userId);
        $this->api->chat($userId)->sendMessage()->text('accountInfo')->keyboard()->rowKeys(function ($m) {
            $m->key('contactInfo');
            $m->key('identityInfo');
        })->rowKeys(function ($m) {
            $m->key('bankInfo');
            $m->key('backward');
        })->exec();
    }

    public function show($message)
    {
        $userId = $message->from->id;
        $this->api->deleteCache($userId);
        
        $cache = $message->cache;
        $key = $cache->key ?? str_replace('Info', '', array_search($message->text, (array) $this->config->keywords));
        $user = User::find($userId);
        $data = $user->{$key};

        $this->api->chat($userId)->sendMessage()->text($key . "Info", $data)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('edit', 'data', 'Account.edit');
        })->exec();

        $cache->key = $key;
        $this->api->setCache($userId, $cache);
    }

    public function edit($callback)
    {
        $key = $callback->cache->key;
        $userId = $callback->from->id;
        $messageId = $callback->message->message_id;

        $this->api->chat($userId)->updateButton()->messageId($messageId)->exec();

        $flow = new FlowBot();
        $flow->start($userId, $key, 'Account', 'update', 'show');
    }

    public function update($result)
    {
        $userId = $result->userId;
        $data = $result->data;

        $cache = $this->api->getCache($userId);
        $key = $cache->key;

        User::find($userId)->{$key}()->update((array)$data);

        $this->api->chat($userId)->sendMessage()->text('saveSuccessfully')->keyboard()->rowKeys(function ($m) {
            $m->key('contactInfo');
            $m->key('identityInfo');
        })->rowKeys(function ($m) {
            $m->key('bankInfo');
            $m->key('backward');
        })->exec();

        $message = new stdClass;
        $message->from = new stdClass;
        $message->from->id = $userId;
        $message->cache = $cache;
        $this->show($message);
    }
}
