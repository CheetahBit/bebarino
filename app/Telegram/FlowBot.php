<?php

namespace App\Telegram;

use App\Models\Country;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use stdClass;

class FlowBot extends ParentBot
{

    public function start($name, $method, $target = '')
    {
        $flow = new stdClass;
        $flow->name = $name;
        $flow->class = $this->config->flows->{$name}->class;
        $flow->method = $method;
        $flow->target = $target;
        $flow->cursor = -1;
        $flow->data = new stdClass;

        $this->putCache('flow', $flow);
        $this->putCache('action', 'flow');
        $this->next();
    }

    public function next()
    {
        $flow = $this->cache->flow;
        $steps = $this->config->flows->{$flow->name}->steps;
        $flow->cursor = count((array)$flow->data);

        if (count($steps) > $flow->cursor) {
            $step = ucfirst($steps[$flow->cursor]);

            $temp = $this->api->chat($this->userId)->sendMessage();

            if (str_contains($flow->name, 'Info') && !str_contains($step, 'Country') && $step != 'paspport') {
                $value = $this->user->account->{$step};
                $temp->rowKeys(function ($m) use ($value) {
                    $m->key($value);
                });
            }

            if ($step == 'Contact') {
                $temp->text('inputContact')->keyboard()->rowKeys(function ($m) {
                    $m->key('sharePhone', 'request_contact', true);
                });
            } else if ($step == 'Ticket') {
                $temp->text('inputTicket')->keyboard()->rowKeys(function ($m) {
                    $m->key('desire');
                });
            } else if (str_contains($step, 'Country')) {
                $temp->text('input' . $step)->keyboard();
                $countries = Country::all();
                foreach ($countries->chunk(3) as $keys) {
                    $temp->rowKeys(function (APIBot $m) use ($keys) {
                        foreach ($keys as $key) $m->key($key->fullTitle());
                    });
                }
                if (in_array($flow->name, $this->config->optionals)) {
                    $temp->rowKeys(function (APIBot $m) {
                        $m->key('desire');
                    });
                }
            } else if (
                str_contains($step, 'Address') && $step != "Address" &&
                User::find($this->userId)->addresses()->where([
                    'country' => $flow->data->toCountry ?? $flow->data->fromCountry,
                    'city' => $flow->data->toCity ?? $flow->data->fromCity,
                ])->exists()
            )
                $temp->text('inputOrSelect' . $step)->inlineKeyboard()->rowButtons(function ($m) {
                    $m->button('selectAddress', 'query', time())->inlineMode('addresses');
                });
            else if (in_array($flow->name, $this->config->optionals))
                $temp->text('input' . $step)->keyboard()->rowKeys(function ($m) {
                    $m->key('desire');
                });
            else $temp->text('input' . $step)->removeKeyboard();


            $temp->exec();
            $this->putCache('flow', $flow);
        } else $this->output($flow);
    }

    public function input()
    {
        $flow = $this->cache->flow;
        $steps = $this->config->flows->{$flow->name}->steps;
        $step = $steps[$flow->cursor];
        $type = $this->update->entities[0]->type ?? null;
        $error = null;

        $desire =  $this->config->keywords->desire;
        $isDesire = ($this->data ?? null) == $desire;

        $this->api->chat($this->userId)->updateButton()->messageId($this->messageId - 1)->exec();

        if ($step == 'contact') {
            if (!isset($this->update->contact))
                $error = 'errorInvalidContact';
            else if ($this->update->contact->user_id != $this->userId)
                $error = 'errorAnotherContact';
            else
                $this->update->data = $this->update->contact->phone_number;
        } else if ($step == 'phone' && $type != 'phone_number' && !$isDesire)
            $error = 'errorInvalidPhone';
        else if ($step == 'email' && $type != 'email' && !$isDesire)
            $error = 'errorInvalidEmail';
        else if ($step == 'date') {
            if (Validator::make(['data' => $this->data], ['data' => 'date_format:Y/m/d'])->fails())
                $error = 'errorInvalidDate';
            else if (Carbon::parse($this->data)->lte(Carbon::today()))
                $error = 'errorDatePast';
        } else if (($step == 'passport' || $step == 'ticket') && !$isDesire && !isset($this->photo))
            $error = 'errorInvalidPhoto';


        if (isset($error))
            $this->api->chat($this->userId)->sendMessage()->text($error)->exec();
        else {
            $flow->data->{$step} = $this->data ?? $this->download($this->photo, $step);
            if (($this->data ?? null) == $desire)
                $flow->data->{$step} = null;
        }

        $this->putCache('flow', $flow);
        $this->next();
    }

    public function output($flow)
    {
        $class = new ("App\Telegram\\" . $flow->class . "Bot")($this->update);
        $class->result = $flow;
        $class->{$flow->method}();
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
