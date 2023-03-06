<?php

namespace App\Telegram;

use App\Models\Address;
use App\Models\Package;
use App\Models\Transfer;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use stdClass;

class TripBot
{

    public $api;

    public function __construct()
    {
        $this->api = new APIBot();
    }

    public function show($message)
    {
        $userId = $message->from->id;
        $id = $message->text;

        $trip = User::find($userId)->trips()->find($id);
        $this->api->chat($userId)->sendMessage()->text('tripInfo', $trip->toArray())->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('delete', 'data', 'Trip.delete');
            $m->button('edit', 'data', 'Trip.edit');
            $m->button('backward', 'data', 'MyRequest.index');
        })->exec();

        $this->api->putCache($userId, 'trip', $id);
    }

    public function edit($callback)
    {
        $userId = $callback->from->id;
        $flow = new FlowBot();
        $flow->start($userId, 'trip', 'Trip', 'update', 'show');
    }

    public function update($data)
    {
        $config = config('telegram');
        $channel = $config->channel;
        $trip = Trip::find($data->id);
        $messageId = $trip->messageId;
        $trip->delete();
        $this->api->chat('@' . $channel)->deleteMessage()->messageId($messageId)->exec();
        $this->submit($data);
    }

    public function create($callback)
    {
        $cache = $callback->cache;
        $userId = $callback->from->id;
        $cache->package = $callback->data;
        $flow = new FlowBot();
        $flow->start($userId, 'trip', 'Trip', 'store', 'form');
        $this->api->setCache($userId, $cache);
    }

    public function store($data)
    {
        $userId = $data->userId;
        $user = User::find($userId);
        $trip = $user->trip()->create((array)$data);
        $id = $trip->id;
        $trip->save();

        $temp = new stdClass;
        $temp->userId = $userId;
        $temp->trip = $id;
        $temp->package = $data->package;

        $this->request($temp);
    }

    public function delete($callback)
    {
        $config = config('telegram');
        $channel = $config->channel;
        $deleted = $config->messages->deleted;
        $userId = $callback->from->id;
        $cache = $callback->cache;
        $text = $callback->message->text . "\n\n" . $deleted;

        $trip = User::find($userId)->trips()->find($cache->trip);
        $messageId = $trip->messageId;
        $trip->delete();
        $this->api->chat('@' . $channel)->deleteMessage()->messageId($messageId)->exec();
        $messageId = $callback->message->message_id;
        $this->api->chat($userId)->updateMessage()->text(plain: $text)->messageId($messageId)->exec();

        (new MyRequestBot())->index($callback);
    }

    public function submit($result)
    {
        $config = config('telegram');
        $channel = $config->channel;
        $userId = $result->userId;
        $data = $result->data;

        $user = User::find($userId);
        $fromAddress = $user->addresses()->find($data->fromAddress)->toArray();
        $toAddress = $user->addresses()->find($data->toAddress)->toArray();
        $data->fromAddress = collect($fromAddress)->join(" , ");
        $data->toAddress = collect($toAddress)->join(" , ");
        if ($config->keywords->desire == $data->ticket) $data->ticket = null;

        $trip = $user->trips()->create((array) $data);
        $id = $trip->id;
        $trip->save();

        $trip = $user->trips()->find($id);
        $trip->checkRequirment();
        $trip->cc();


        $result = $this->api->chat('@' . $channel)->sendMessage()->text('channelTrip', $trip)
            ->inlineKeyboard()->rowButtons(function ($m) use ($id) {
                $m->button('sendFormRequest', 'data', 'Package.form.' . $id);
            })->exec();

        $args = [
            "channel" => $channel,
            "post" => $result->message_id
        ];
        $this->api->chat($userId)->sendMessage()->text('tripSubmitted', $args)->exec();

        $user->trips()->find($id)->update([
            'messageId' => $result->message_id
        ]);

        $message = new stdClass;
        $message->from = (object)['id' => $userId];
        $main = new MainBot();
        $main->menu($message);
    }

    public function form($callback)
    {
        $config = config('telegram');
        $channel = $config->channel;
        $userId = $callback->from->id;
        $text = $callback->message->text;
        $messageId = $callback->message->message_id;
        $package = $callback->data;

        $main = new MainBot();
        if ($main->checkLogin($userId)) {
            $transfer = Transfer::where(['package' => $package, 'status' => 'verified']);
            if (Package::find($package)->user->id == $userId)
                $main->api->showAlert($callback->id, true)->text('requestSelf')->exec();
            else if ($transfer->exists()) {
                $main->api->showAlert($callback->id, true)->text('requestIsDone')->exec();
                $this->api->chat('@' . $channel)->updateButton()->inlineKeyboard()->rowButtons(function ($m) use ($channel) {
                    $m->button('requestDone', 'url', 't.me/' . $channel);
                })->messageId($messageId);
            } else {
                $main->api->showAlert($callback->id, true)->text('requestFormSent')->exec();
                $main->api->chat($userId)->sendMessage()->text(key: 'requestTrip', plain: $text)
                    ->inlineKeyboard()->rowButtons(function ($m) use ($package) {
                        $m->button('selectTrip', 'query', time())->inlineMode('selectTrip');
                        $m->button('createTrip', 'data', 'Trip.create.' . $package);
                    })->exec();
                $action = $config->actions->selectPackage;
                $this->api->putCache($userId, 'action', $action);
                $this->api->putCache($userId, 'package', $package);
            }
        } else $main->needLogin($userId);
    }

    public function select($message)
    {
        $userId = $message->from->id;
        $id = $message->text;

        $data = new stdClass;
        $data->userId = $userId;
        $data->package = $id;
        $data->package = $message->cache->package;

        $this->request($data);
    }

    public function request($data)
    {
        $userId = $data->userId;
        Cache::delete($userId);
        $user = User::find($userId);
        $trip = $user->trips()->find($data->trip);
        $package = $user->packages()->find($data->package);

        $this->api->chat($package->user->id)->sendMessage()->text('requestPackage', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($data) {
            $data = $data->trip . ',' . $data->package;
            $m->button('acceptRequest', 'data', 'Trip.accept.' . $data);
            $m->button('rejectRequest', 'data', 'Trip.reject.' . $data);
        })->exec();

        $pending = config('telegram')->messages->pending;
        $this->api->chat($userId)->sendMessage()->text('requestTrip', $trip, $pending)->exec();
    }

    public function reject($callback)
    {
        $config = config('telegram');
        $userId = $callback->from->id;
        $data = explode(',', $callback->data);
        $trip = Trip::find($data[0]);
        $package = Package::find($data[1]);
        $messageId = $callback->message->message_id;
        $text = $callback->message->text;
        $reject = $config->messages->rejectRequest;

        if (in_array($userId, $config->admins)) {

            $this->api->chat($userId)->updateMessage()->text(plain: $text . "\n\n" . $reject)->messageId($messageId)->exec();
            $this->api->chat($trip->userId)->sendMessage()->text('requestTrip', $trip, "\n\n" . $reject)->exec();
            $this->api->chat($package->userId)->sendMessage()->text('requestTrip', $trip, "\n\n" . $reject)->exec();
        } else {
            $text .= "\n\n" . $reject;
            $this->api->chat($userId)->updateMessage()->text(plain: $text)->messageId($messageId)->exec();
            $this->api->chat($package->userId)->sendMessage()->text(plain: $text)->exec();
        }
    }

    public function accept($callback)
    {
        $config = config('telegram');
        $userId = $callback->from->id;
        $data = explode(',', $callback->data);
        $package = Package::find($data[1]);
        $trip = Trip::find($data[0]);
        $messageId = $callback->message->message_id;
        $id = $callback->id;
        $text = $callback->message->text;
        $accept = $config->messages->acceptRequest;
        $pendding = $config->messages->penddingAdmin;
        $args = [];

        if (in_array($userId, $config->admins)) {
            $user = User::find($package->userId);
            $ticket = $package->ticket;
            $passport = $user->identity()->passport;
            $contact = $user->contact()->isCompelete();
            if (!isset($ticket)) $this->api->showAlert($id, true)->text('noTicket')->exec();
            else if (!isset($passport)) $this->api->showAlert($id, true)->text('noPassport')->exec();
            else if (!$contact) $this->api->showAlert($id, true)->text('noContact')->exec();
            else {
                $text .= "\n\n" . $accept;
                $this->api->chat($userId)->updateMessage()->text(plain: $text)->messageId($messageId)->rowButtons(function ($m)  use ($trip, $package) {
                    $m->button('contactTripper', 'url', 'tg://user?id=' . $package->userId);
                    $m->button('contactPacker', 'url', 'tg://user?id=' .  $trip->userId);
                })->exec();
                $this->api->chat($package->userId)->sendMessage()->text(plain: $text)->rowButtons(function ($m)  use ($trip) {
                    $m->button('contactPacker', 'url', 'tg://user?id=' .  $trip->userId);
                })->exec();
                $this->api->chat($trip->userId)->sendMessage()->text(plain: $text)->rowButtons(function ($m)  use ($package) {
                    $m->button('contactTripper', 'url', 'tg://user?id=' . $package->userId);
                })->exec();
            }
        } else {
            $text .= "\n\n" . $accept . "\n\n" . $pendding;
            $this->api->chat($package->userId)->sendMessage()->text(plain: $text)->exec();
            $this->api->chat($trip->userId)->updateMessage()->text(plain: $text)->messageId($messageId)->exec();
            foreach ($config->admins as $admin)
                $this->api->chat($admin)->sendMessage()->text('requestTripAdmin', $args)->inlineKeyboard()->rowButtons(function ($m) use ($trip, $package) {
                    $m->button('contactTripper', 'url', 'tg://user?id=' . $package->userId);
                    $m->button('contactPacker', 'url', 'tg://user?id=' .  $trip->userId);
                })->rowButtons(function ($m) use ($data) {
                    $data = implode(',', $data);
                    $m->button('acceptRequest', 'data', 'Trip.accept.' . $data);
                    $m->button('rejectRequest', 'data', 'Trip.reject.' . $data);
                })->exec();
        }
    }
}
