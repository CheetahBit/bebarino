<?php

namespace App\Telegram;

use App\Models\Country;
use ReflectionClass;
use stdClass;

class FlowBot
{
    public $api;
    public $userId;

    public function __construct()
    {
        $this->api = new APIBot();
    }

    public function start($userId, $name, $class, $method, $backward)
    {
        $config = config('telegram');
        $this->userId = $userId;

        $data = new stdClass;
        $data->userId = $userId;
        $data->name = $name;
        $data->class = $class;
        $data->method = $method;
        $data->backward = $backward;
        $data->cursor = -1;

        $this->api->putCache($userId, 'flow', $data);
        $this->api->putCache($userId, 'action', $config->actions->flow);
        $this->next();
    }

    public function next()
    {
        $config = config('telegram');
        $cache = $this->api->getCache($this->userId)->flow;
        $cache->cursor++;
        $flow = $config->flows->{$cache->name};
        if (count($flow) > $cache->cursor) {
            $step = $flow[$cache->cursor];
            $temp = $this->api->chat($cache->userId)->sendMessage();
            if ($step == 'contact') {
                $temp->text('inputContact')->keyboard()->rowKeys(function ($m) {
                    $m->key('sharePhone', 'request_contact', true);
                    $m->key('backward');
                });
            } else if ($step == 'country') {
                $temp->text('inputCountry')->keyboard();
                $countries = Country::all();
                foreach ($countries->chunk(3) as $keys) {
                    $temp->rowKeys(function (APIBot $m) use ($keys) {
                        foreach ($keys as $key) $m->key($key->title);
                    });
                }
                $temp->rowKeys(function (APIBot $m) {
                    $m->key('backward');
                });
            } else if (str_contains($step, 'Address')) {
                $temp->text('selectAddress')->inlineKeyboard()->rowButtons(function ($m) {
                    $m->button('selectPackage', 'query', time())->inlineMode('selectPackage');
                    $m->button('backward', 'data', 'Flow.prev');
                });
            } else $temp->text('input' . ucfirst($step))->keyboard()->rowKeys(function (APIBot $m) {
                $m->key('backward');
            });
            $temp->exec();
            $this->api->putCache($cache->userId, 'flow', $cache);
        } else $this->output($cache);
    }

    public function input($message)
    {
        $config = config('telegram');
        $userId = $message->from->id;
        $cache = $message->cache->flow;
        $flow = $config->flows->{$cache->name};
        $step = $flow[$cache->cursor];
        $type = $message->entities[0]->type ?? null;
        $error = null;

        if ($step == 'contact') {
            if (!isset($message->contact)) $error = '';
            else if ($message->contact->user_id == $userId) $error = '';
        } else if ($step == 'phone' && $type != 'phone_number') $error = '';
        else if ($step == 'email' && $type != 'email')  $error = '';
        else if (($step == 'passport' || $step == 'ticket') && !isset($message->photo))  $error = '';

        if (isset($error)) $this->api->chat($userId)->sendMessage()->text($error)->exec();
        else $cache->data->{$step} = $message->text ?: $this->download($message->photo, $step);

        $this->api->putCache($userId, 'flow', $cache);
        $this->next();
    }

    public function backward($message)
    {
        $userId = $message->from->id;
        $cache = $this->api->getCache($userId);
        $flow = $cache->flow;
        $flow->cursor -= 2;
        if ($cache->cursor >= 0) {
            $this->api->putCache($userId, 'flow', $flow);
            $this->next();
        } else {
            $message = new stdClass;
            $message->from->id = $userId;
            $message->cache = $cache;
            if ($flow->backward == 'menu') (new MainBot)->menu($message);
            else {
                $class = new ReflectionClass($flow->class . "Bot");
                $class->{$flow->backward}($message);
            }
        };
    }

    public function output($result)
    {
        $class = new ("App\Telegram\\" . $result->class . "Bot")();
        $class->{$result->method}($result);
    }

    public function download($photo, $step)
    {
        if (isset($photo)) {
            $photo = end($photo);
            (new APIBot())->download($photo, $step . 's');
            return $photo->file_unique_id;
        }
        return null;
    }
}
