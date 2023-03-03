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

        $messageId = $message->message_id - 1;
        $this->api->chat($userId)->updateButton()->messageId($messageId)->exec();

        $this->api->chat($userId)->sendMessage()->text('myRequests')->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('indexRequest', 'query', time())->inlineMode('requests');
        })->exec();

        $action = config('telegram')->actions->myRequestShow;
        $this->api->putCache($userId, 'action', $action);
    }

    public function show($message)
    {
        $userId = $message->from->id;
        $text = $message->text;

        $data = explode('-', $text);
        $message->text = $data[1];
        match($data[0]){
            "package" => (new PackageBot())->show($message),
            "trip" => (new TripBot())->show($message)
        };

        $messageId = $message->message_id - 1;
        $this->api->chat($userId)->updateButton()->messageId($messageId)->exec();
    }
}
