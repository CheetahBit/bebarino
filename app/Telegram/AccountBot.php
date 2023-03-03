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
        $cache = $message->cache;
        $key = $cache->key ?: str_replace('Info', '', array_search($message->text, $this->config->keywords));
        $userId = $message->from->id;
        $user = User::find($userId);
        $data = $user->{$key};

        $this->api->chat($userId)->sendMessage()->text($key . "Info", $data)->keyboard()->rowKeys(function ($m) {
            $m->key('edit');
            $m->key('backward');
        })->exec();

        $cache->key = $key;
        $this->api->setCache($userId, $cache);
    }

    public function edit($message)
    {
        $key = $message->cache->key;
        $userId = $message->from->id;

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

        $this->api->chat($userId)->sendMessage()->text('saveSuccessfully')->exec();
        
        $message = new stdClass;
        $message->from->id = $userId;
        $message->cache = $cache;

        $this->show($message);
    }
}
