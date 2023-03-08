<?php

namespace App\Telegram;

use App\Models\Country;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
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

        $flow = new stdClass;
        $flow->userId = $userId;
        $flow->name = $name;
        $flow->class = $class;
        $flow->method = $method;
        $flow->backward = $backward;
        $flow->cursor = -1;
        $flow->data = new stdClass;

        $this->api->putCache($userId, 'flow', $flow);
        $this->api->putCache($userId, 'action', $config->actions->flow);
        $this->next();
    }

    public function next()
    {
        $config = config('telegram');
        $cache = $this->api->getCache($this->userId)->flow;
        $cache->cursor = count((array)$cache->data);
        $flow = $config->flows->{$cache->name};
        if (count($flow) > $cache->cursor) {
            $step = ucfirst($flow[$cache->cursor]);

            $temp = $this->api->chat($cache->userId)->sendMessage();
            if ($step == 'Contact') {
                $temp->text('inputContact')->keyboard()->rowKeys(function ($m) {
                    $m->key('sharePhone', 'request_contact', true);
                });
            } else if ($step == 'Ticket') {
                $temp->text('inputTicket')->keyboard()->rowKeys(function ($m) {
                    $m->key('desire');
                });
            } else if (str_contains($step, 'Country')) {
                $temp->text('inputCountry')->keyboard();
                $countries = Country::all();
                foreach ($countries->chunk(3) as $keys) {
                    $temp->rowKeys(function (APIBot $m) use ($keys) {
                        foreach ($keys as $key) $m->key($key->fullTitle());
                    });
                }
                if (in_array($cache->name, $config->optionals)) {
                    $temp->rowKeys(function (APIBot $m) {
                        $m->key('desire');
                    });
                }
            } else if (
                str_contains($step, 'Address') && $step != "Address" &&
                User::find($this->userId)->addresses()->where([
                    'country' => $cache->data->toCountry ?? $cache->data->fromCountry,
                    'city' => $cache->data->toCity ?? $cache->data->fromCity,
                ])->exists()
            )
                $temp->text('inputOrSelect' . $step)->inlineKeyboard()->rowButtons(function ($m) {
                    $m->button('selectAddress', 'query', time())->inlineMode('addresses');
                });
            else if (in_array($cache->name, $config->optionals))
                $temp->text('input' . $step)->keyboard()->rowKeys(function ($m) {
                    $m->key('desire');
                });
            else $temp->text('input' . $step)->removeKeyboard();
            $temp->exec();
            $this->api->putCache($cache->userId, 'flow', $cache);
        } else $this->output($cache);
    }

    public function input($message)
    {
        $config = config('telegram');
        $this->userId = $message->from->id;
        $messageId = $message->message_id;
        $cache = $message->cache->flow;
        $flow = $config->flows->{$cache->name};
        $step = $flow[$cache->cursor];
        $type = $message->entities[0]->type ?? null;
        $error = null;
        
        $isDesire = ($message->text ?? null) == $config->keywords->desire;
        $this->api->chat($this->userId)->updateButton()->messageId($messageId - 1)->exec();
        if ($step == 'contact') {
            if (!isset($message->contact)) $error = 'errorInvalidContact';
            else if ($message->contact->user_id != $this->userId) $error = 'errorAnotherContact';
            else $message->text = $message->contact->phone_number;
        } else if ($step == 'phone' && $type != 'phone_number' && !$isDesire) $error = 'errorInvalidPhone';
        else if ($step == 'email' && $type != 'email' && !$isDesire)  $error = 'errorInvalidEmail';
        else if ($step == 'date') {
            if (Validator::make((array)$message, ['text' => 'date_format:Y/m/d'])->fails()) $error = 'errorInvalidDate';
            else if (Carbon::now()->eq(Carbon::parse($message->text))) $error = 'errorDatePast';
        } else if (($step == 'passport' || $step == 'ticket') && !$isDesire && !isset($message->photo))  $error = 'errorInvalidPhoto';


        if (isset($error)) $this->api->chat($this->userId)->sendMessage()->text($error)->exec();
        else {
            $cache->data->{$step} = $message->text ?? $this->download($message->photo, $step);
            if (($message->text ?? null) == $config->keywords->desire) $cache->data->{$step} = null;
        }



        $this->api->putCache($this->userId, 'flow', $cache);
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
