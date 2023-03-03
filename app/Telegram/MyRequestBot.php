<?php

namespace App\Telegram;

use App\Models\User;
use stdClass;

class MyRequestBot
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

        $this->api->chat($userId)->sendMessage()->text('myRequests')->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('selectRequest', 'query', time())->inlineMode('requests');
            $m->button('backward', 'data', 'Main.menu');
        })->exec();

        $cache->action = config('telegram.actions.myRequestShow');
        $this->api->setCache($userId, $cache);
    }

    public function show($message)
    {
        $userId = $message->from->id;
        $id = $message->text;

        $user = User::find($userId);
        
        $package = $user->packages()->find($id);
        if (isset($package)) return (new PackageBot())->show($message);

        $trip = $user->trips()->find($id);
        if (isset($trip)) (new TripBot())->show($message);
    }
}
