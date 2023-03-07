<?php

namespace App\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use stdClass;

class MainBot
{
    public $api;

    public function __construct()
    {
        $this->api = new APIBot();
    }

    public function menu($message)
    {
        $userId = $message->from->id;
        $this->api->deleteCache($userId);

        $isLogged = false;
        if ($this->checkExistsUser($userId)) $isLogged = $this->checkLogin($userId);
        $menu =  $isLogged ? 'mainMenu' : 'guestMenu';

        $this->api->chat($userId)->sendMessage()->text($menu)->keyboard()->rowKeys(function (APIBot $m)  use ($isLogged) {
            $m->key($isLogged ? 'account' : 'beginning');
        })->rowKeys(function (APIBot $m) use ($isLogged) {
            if ($isLogged) {
                $m->key('submitTrip');
                $m->key('submitPackage');
            }
        })->rowKeys(function (APIBot $m) use ($isLogged) {
            if ($isLogged) {
                $m->key('myAddresses');
                $m->key('myRequests');
            }
        })->rowKeys(function (APIBot $m) {
            $m->key('support');
            $m->key('aboutUs');
        })->exec();

        if (isset($message->message)) {
            $messageId = $message->message->message_id;
            $this->api->chat($userId)->updateButton()->messageId($messageId)->exec();
        }
    }

    public function beginning($message)
    {
        $userId = $message->from->id;
        $user = User::find($userId);
        if (isset($user->phone))
            $this->api->chat($userId)->sendMessage()->text('alreadyLogged')->exec();
        else {
            $flow = new FlowBot();
            $flow->start($userId, 'beginning', 'Main', 'login', 'menu');
        }
    }

    public function login($result)
    {
        $userId = $result->userId;
        $data = $result->data;

        $user = User::find($userId);
        $user->update(['phone' => $data->contact]);
        $user->contact()->update(['phone' => $data->contact]);

        $this->api->chat($userId)->sendMessage()->text('loginSuccessfully')->exec();

        $message = new stdClass;
        $message->from = new stdClass;
        $message->from->id = $userId;
        $this->menu($message);
    }

    public function support($message)
    {
        $userId = $message->from->id;
        $this->api->chat($userId)->sendMessage()->text('support')->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('contactSupport', 'url', 't.me/' . config('telegram.support'));
        })->exec();
    }

    public function aboutUs($message)
    {
        $userId = $message->from->id;
        $this->api->chat($userId)->sendMessage()->text('aboutUs')->exec();
    }

    public function submitTrip($message)
    {
        $userId = $message->from->id;
        if ($this->checkLogin($userId)) {
            $this->api->chat($userId)->sendMessage()->text('removeKeyboard')->removeKeyboard()->exec();
            $this->api->chat($userId)->sendMessage()->text('submitTrip')->inlineKeyboard()->rowButtons(function($m){
                $m->button('backward', 'data', 'Main.menu');
            })->exec();
            $flow = new FlowBot();
            $flow->start($userId, 'trip', 'Trip', 'confirmSubmit', 'menu');
        } else $this->needLogin($userId);
    }

    public function submitPackage($message)
    {
        $userId = $message->from->id;
        if ($this->checkLogin($userId)) {
            $this->api->chat($userId)->sendMessage()->text('removeKeyboard')->removeKeyboard()->exec();
            $this->api->chat($userId)->sendMessage()->text('submitPackage')->inlineKeyboard()->rowButtons(function($m){
                $m->button('backward', 'data', 'Main.menu');
            })->exec();
            $flow = new FlowBot();
            $flow->start($userId, 'package', 'Package', 'confirmSubmit', 'menu');
        } else $this->needLogin($userId);
    }

    public function checkLogin($userId)
    {
        return (bool) User::find($userId)?->phone ?? false;
    }

    private function checkExistsUser($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            $user = User::create(['id' => $userId]);
            $user->identity()->create()->save();
            $user->bank()->create()->save();
            $user->contact()->create()->save();
            $user->save();
        } else return true;
        return false;
    }

    public function needLogin($userId)
    {
        $this->api->chat($userId)->sendMessage()->text('needLogin')->exec();
        $message = (object)["from" => (object)["id" => $userId]];
        $this->menu($message);
    }
}
