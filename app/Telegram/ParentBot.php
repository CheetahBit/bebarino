<?php

namespace App\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ParentBot
{
    public $update;
    public $api;
    public $config;
    public $cache;
    public $type;
    public $userId;
    public $user;
    public $messageId;
    public $callbackId;
    public $inlineId;
    public $data;
    public $text;
    public $photo;
    public $result;

    public function __construct($update)
    {
        $this->update = $update;
        $this->api = new APIBot();
        $this->config = config('telegram');
        $this->type = $update->type;
        $this->userId = $update->from->id;
        $this->cache = json_decode(Cache::get($this->userId, '{}'));
        $this->user = User::find($this->userId);
        $this->messageId = $update->message_id ?? $update->message->message_id;
        $this->data = $update->data ?? $update->text;
        $this->text = $update->text ?? $update->message->text;
        $this->callbackId = $update->id ?? null;
        $this->inlineId = $update?->id ?? null;
        $this->photo = $update?->photo ?? null;

        $this->api->chat($this->userId);
    }


    public function putCache($key, $value)
    {
        $this->cache->{$key} = $value;
        Cache::put($this->userId, json_encode($this->cache, JSON_UNESCAPED_UNICODE));
    }

    public function clear()
    {
        Cache::store('database')->delete($this->userId);
    }
}
