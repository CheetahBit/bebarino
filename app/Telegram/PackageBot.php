<?php

namespace App\Telegram;

use App\Models\Package;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use stdClass;

class PackageBot
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

        $package = User::find($userId)->packages()->find($id);
        $this->api->chat($userId)->sendMessage()->text('packageInfo', $package)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('delete', 'data', 'Package.delete');
            $m->button('edit', 'data', 'Package.edit');
            $m->button('backward', 'data', 'Package.index');
        })->exec();
    }

    public function edit($callback)
    {
        $cache = $callback->cache;
        $userId = $callback->from->id;
        $cache->package = $callback->data;
        $flow = new FlowBot();
        $flow->start($userId, 'package', 'Package', 'update', 'show');
        $this->api->setCache($userId, $cache);
    }

    public function update($data)
    {
        $config = config('telegram');
        $channel = $config->channel;
        $package = Package::find($data->id);
        $messageId = $package->messageId;
        $package->delete();

        $this->api->chat('@' . $channel)->deleteMessage()->messageId($messageId)->exec();

        $this->submit($data);
    }

    public function create($callback)
    {
        $cache = $callback->cache;
        $userId = $callback->from->id;
        $cache->trip = $callback->data;
        $flow = new FlowBot();
        $flow->start($userId, 'package', 'Package', 'store', 'form');
        $this->api->setCache($userId, $cache);
    }

    public function store($data)
    {
        $userId = $data->userId;
        $user = User::find($userId);
        $package = $user->packages()->create((array)$data);
        $id = $package->id;
        $package->save();

        $temp = new stdClass;
        $temp->userId = $userId;
        $temp->package = $id;
        $temp->trip = $data->trip;

        $this->request($temp);
    }

    public function delete($callback)
    {
        $config = config('telegram');
        $channel = $config->channel;
        $deleted = $config->messages->deleted;
        $userId = $callback->from->id;
        $data = $callback->data;
        $text = $callback->message->text . "\n\n" . $deleted;

        $package = User::find($userId)->packages()->find($data);
        $messageId = $package->messageId;
        $package->delete();
        $this->api->chat('@' . $channel)->deleteMessage()->messageId($messageId)->exec();
        $messageId = $callback->message->message_id;
        $this->api->chat($userId)->updateMessage()->text(plain: $text)->messageId($messageId)->exec();
    }

    public function submit($result)
    {
        $channel = config('telegram')->channel;
        $userId = $result->userId;
        $data = $result->data;

        $user = User::find($userId);
        $fromAddress = $user->addresses()->find($data->fromAddress)->toArray();
        $toAddress = $user->addresses()->find($data->toAddress)->toArray();
        $data->fromAddress = collect($fromAddress)->join(" , ");
        $data->toAddress = collect($toAddress)->join(" , ");

        $package = $user->packages()->create((array) $data);
        $id = $package->id;
        $package->save();
        $package = $user->packages()->find($id);
        $package->cc();
        
        $result = $this->api->chat('@' . $channel)->sendMessage()->text('channelPackage', $package)
            ->inlineKeyboard()->rowButtons(function ($m) use ($id) {
                $m->button('sendFormRequest', 'data', 'Trip.form.' . $id);
            })->exec();

        $args = [
            "channel" => $channel,
            "post" => $result->message_id
        ];
        $this->api->chat($userId)->sendMessage()->text('packageSubmitted', $args)->exec();

        $user->packages()->find($id)->update([
            'message_id' => $result->message_id
        ]);

        $message = new stdClass;
        $message->from = (object)['id' => $userId];
        $main = new MainBot();
        $main->menu($message);
    }

    public function form($callback)
    {
        $userId = $callback->from->id;
        Cache::delete($userId);
        $main = new MainBot();
        if ($main->checkLogin($userId)) {
            $text = $callback->message->text;
            $trip = $callback->data;
            $main->api->chat($userId)->sendMessage()->text(key: 'requestPackage', plain: $text)
                ->inlineKeyboard()->rowButtons(function ($m) use ($trip) {
                    $m->button('selectPackage', 'query', time())->inlineMode('selectPackage');
                    $m->button('createPackage', 'data', 'Package.create.' . $trip);
                })->exec();
        } else $main->needLogin($userId);
    }

    public function select($message)
    {
        $userId = $message->from->id;
        $id = $message->text;

        $data = new stdClass;
        $data->userId = $userId;
        $data->package = $id;
        $data->trip = $message->cache->trip;

        $this->request($data);
    }

    public function request($data)
    {
        $userId = $data->userId;
        $user = User::find($userId);
        $package = $user->packages()->find($data->package);
        $trip = $user->trips()->find($data->trip);

        $this->api->chat($trip->user->id)->sendMessage()->text('requestTrip', $package)->inlineKeyboard()->rowButtons(function ($m) use ($data) {
            $data = $data->trip . ',' . $data->package;
            $m->button('acceptRequest', 'data', 'Package.accept.' . $data);
            $m->button('rejectRequest', 'data', 'Package.reject.' . $data);
        })->exec();

        $pending = config('telegram')->messages->pending;
        $this->api->chat($userId)->sendMessage()->text('requestPackage', $package, $pending)->exec();
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
            $this->api->chat($package->userId)->sendMessage()->text('requestTrip', $package, "\n\n" . $reject)->exec();
            $this->api->chat($trip->userId)->sendMessage()->text('requestTrip', $package, "\n\n" . $reject)->exec();
        } else {
            $text .= "\n\n" . $reject;
            $this->api->chat($userId)->updateMessage()->text(plain: $text)->messageId($messageId)->exec();
            $this->api->chat($trip->userId)->sendMessage()->text(plain: $text)->exec();
        }
    }

    public function accept($callback)
    {
        $config = config('telegram');
        $userId = $callback->from->id;
        $data = explode(',', $callback->data);
        $trip = Trip::find($data[0]);
        $package = Package::find($data[1]);
        $messageId = $callback->message->message_id;
        $id = $callback->id;
        $text = $callback->message->text;
        $accept = $config->messages->acceptRequest;
        $pendding = $config->messages->penddingAdmin;
        if (in_array($userId, $config->admins)) {
            $user = User::find($trip->userId);
            $ticket = $trip->ticket;
            $passport = $user->identity()->passport;
            $contact = $user->contact()->isCompelete();
            if (!isset($ticket)) $this->api->showAlert($id, true)->text('noTicket')->exec();
            else if (!isset($passport)) $this->api->showAlert($id, true)->text('noPassport')->exec();
            else if (!$contact) $this->api->showAlert($id, true)->text('noContact')->exec();
            else {
                $text .= "\n\n" . $accept;
                $this->api->chat($userId)->updateMessage()->text(plain: $text)->messageId($messageId)->rowButtons(function ($m)  use ($package, $trip) {
                    $m->button('contactTripper', 'url', 'tg://user?id=' . $trip->userId);
                    $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
                })->exec();
                $this->api->chat($package->userId)->sendMessage()->text(plain: $text)->rowButtons(function ($m)  use ($trip) {
                    $m->button('contactTripper', 'url', 'tg://user?id=' . $trip->userId);
                })->exec();
                $this->api->chat($trip->userId)->sendMessage()->text(plain: $text)->rowButtons(function ($m)  use ($package) {
                    $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
                })->exec();
            }
        } else {
            $text .= "\n\n" . $accept . "\n\n" . $pendding;
            $this->api->chat($package->userId)->updateMessage()->text(plain: $text)->messageId($messageId)->exec();
            $this->api->chat($trip->userId)->sendMessage()->text(plain: $text)->exec();
            foreach ($config->admins as $admin)
                $this->api->chat($admin)->sendMessage()->text('requestPackageAdmin', array_merge($trip, $package))->inlineKeyboard()->rowButtons(function ($m) use ($package, $trip) {
                    $m->button('contactTripper', 'url', 'tg://user?id=' . $trip->userId);
                    $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
                })->rowButtons(function ($m) use ($data) {
                    $data = implode(',', $data);
                    $m->button('acceptRequest', 'data', 'Package.accept.' . $data);
                    $m->button('rejectRequest', 'data', 'Package.reject.' . $data);
                })->rowButtons(function ($m) use ($trip) {
                    $m->button('imageDocs', 'data', 'Package.imageDocs.' . $trip);
                })->exec();
        }
    }

    public function imageDocs($callback)
    {
        $userId = $callback->from->id;
        $messageId = $callback->message->message_id;
        $trip = Trip::find($callback->data);
        $ticket = $trip->ticket;
        $passport = $trip->user->identity()->passport;
        $paths = [
            "passports/" . $passport,
            "tickets/" . $ticket,
        ];
        $this->api->chat($userId)->sendMediaGroup()->media(function ($m) use ($paths) {
            foreach ($paths as $path) $m->photo($path);
        })->reply($messageId)->exec();
    }
}
