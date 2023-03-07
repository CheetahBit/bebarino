<?php

namespace App\Telegram;

use App\Models\Address;
use App\Models\Package;
use App\Models\Transfer;
use App\Models\Trip;
use App\Models\User;

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
        $config = config('telegram');
        $userId = $message->from->id;
        $isAdmin = in_array($userId, $config->admins);
        $id = $message->text ?? $message->cache->trip;

        if (!isset($message->text)) $this->api->chat($userId)->updateButton()->messageId($message->message->message_id)->exec();

        $trip = User::find($userId)->trips()->find($id);
        $this->api->chat($userId)->sendMessage()->text('tripInfo', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($trip, $isAdmin) {
            if ($isAdmin) {
                $m->button('contactTripper', 'url', 'tg://user?id=' .  $trip->userId);
                $m->button('closeRequest', 'data', 'Trip.close' .  $trip->id);
            } else {
                $m->button('delete', 'data', 'Trip.delete');
                $m->button('edit', 'data', 'Trip.edit');
                $m->button('backward', 'data', 'MyRequest.index');
            }
        })->rowButtons(function ($m) use ($trip, $isAdmin) {
            if (!$isAdmin) {
                if ($trip->getRawOriginal('status') == 'closed') $m->button('openRequest', 'data', 'Trip.status.opened,' .  $trip->id);
                else $m->button('closeRequest', 'data', 'Trip.status.closed,' .  $trip->id);
            }
        })->exec();

        $this->api->putCache($userId, 'trip', $id);
    }

    public function status($callback)
    {
        $data = explode(",", $callback->data);
        $status = $data[0];
        $trip = $data[1];
        $userId = $callback->from->id;
        $messageId = $callback->message->message_id;
        $id = $callback->id;

        $this->api->showAlert($id)->text('request' . ucfirst($status))->exec();

        $user = User::find($userId);
        $user->trips()->find($trip)->update(['status' => $status]);

        $trip = $user->trips()->find($trip);

        $this->api->chat($userId)->updateMessage()->text('tripInfo', $trip)->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('delete', 'data', 'Trip.delete');
            $m->button('edit', 'data', 'Trip.edit');
            $m->button('backward', 'data', 'MyRequest.index');
        })->rowButtons(function ($m) use ($trip) {
            if ($trip->getRawOriginal('status') == 'closed') $m->button('openRequest', 'data', 'Trip.status.opened,' .  $trip->id);
            else $m->button('closeRequest', 'data', 'Trip.status.closed,' .  $trip->id);
        })->exec();

        $trip->requirement();

        if (isset($trip->messageId)) {
            $config = config('telegram');
            $channel = $config->channel;
            $trip->statues = $status;
            $this->api->chat('@' . $channel)->updateMessage()->text('channelTrip', $trip)->messageId($trip->messageId)->inlineKeyboard()->rowButtons(function ($m) use ($trip, $config) {
                if ($trip->getRawOriginal('status') == 'opened') $url = 't.me/' . $config->bot . '?start=trip-' . $trip->id;
                else $url = 't.me/' . $config->channel;
                $m->button('sendFormRequest', 'url', $url);
            })->exec();
        }

    }

    public function close($callback)
    {
        $userId = $callback->from->id;
        $messageId = $callback->message->message_id;
        $id = $callback->id;
        $data = $callback->data;

        $this->api->showAlert($id)->text('requestClosed')->exec();
        $trip = Trip::find($data);
        $this->api->chat($userId)->updateButton()->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) use ($trip) {
            $m->button('contactTripper', 'url', 'tg://user?id=' .  $trip->userId);
        })->exec();
        $this->api->chat($trip->user->id)->sendMessage()->text('requestClosedByAdmin', $trip->id)->exec();

        if (isset($trip->messageId)) {
            $config = config('telegram');
            $channel = $config->channel;
            $trip->statues = 'closedByAdmin';
            $this->api->chat('@' . $channel)->updateButton()->text('channelTrip', $trip)->messageId($trip->messageId)->inlineKeyboard()->rowButtons(function ($m) use ($trip, $config) {
                $m->button('sendFormRequest', 'url', 't.me/' . $config->channel);
            })->exec();
        }
        $trip->update(['status' => 'closedByAdmin']);
    }

    public function edit($callback)
    {
        $userId = $callback->from->id;
        $messageId =  $callback->message->message_id ?? $callback->message_id - 1;

        $flow = new FlowBot();
        $flow->start($userId, 'trip', 'Trip', 'update', 'form');

        $this->api->chat($userId)->updateButton()->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('selectAddress', 'query', time())->inlineMode('addresses');
            $m->button('backward', 'data', 'Trip.show');
        })->exec();
    }

    public function update($result)
    {
        $config = config('telegram');
        $channel = $config->channel;
        $userId = $result->userId;
        $data = $result->data;
        $id = $this->api->getCache($userId)->trip;

        $user = User::find($userId);
        $trip = $user->trips()->find($id);

        $fromAddress = $user->addresses()->find($data->fromAddress)->toArray();
        $toAddress = $user->addresses()->find($data->toAddress)->toArray();
        $data->fromAddress = collect($fromAddress)->join(" , ");
        $data->toAddress = collect($toAddress)->join(" , ");

        $trip->update((array)$data);

        $trip = $user->trips()->find($id);
        $message = (object)[
            "from" => (object)["id" => $userId],
            "text" => $trip->id
        ];
        $this->show($message);

        $messageId = $trip->messageId;
        if (isset($messageId)) {
            $result = $this->api->chat('@' . $channel)->updateMessage()->text('channelTrip', $trip)->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) use ($trip) {
                $m->button('sendFormRequest', 'data', 'Package.form.' . $trip->id);
            })->exec();

            if (!isset($result)) {
                $result = $this->api->sendMessage()->exec();
                $this->api->deleteMessage()->messageId($messageId)->exec();
                $trip->update(['messageId' => $result->message_id]);
            }
        }
    }

    public function create($callback)
    {
        $userId = $callback->from->id;
        $messageId =  $callback->message->message_id ?? $callback->message_id - 1;

        $flow = new FlowBot();
        $flow->start($userId, 'trip', 'Trip', 'store', 'form');

        $this->api->chat($userId)->updateButton()->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('selectAddress', 'query', time())->inlineMode('addresses');
        })->exec();
    }

    public function store($result)
    {
        $userId = $result->userId;
        $data = $result->data;
        $user = User::find($userId);

        $fromAddress = $user->addresses()->find($data->fromAddress)->toArray();
        $toAddress = $user->addresses()->find($data->toAddress)->toArray();
        $data->fromAddress = collect($fromAddress)->join(" , ");
        $data->toAddress = collect($toAddress)->join(" , ");

        $trip = $user->trips()->create((array)$data);
        $id = $trip->id;
        $trip->save();

        $temp = new stdClass;
        $temp->userId = $userId;
        $temp->trip = $id;
        $temp->package = $this->api->getCache($userId)->package;

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

    public function confirm($result)
    {
        $userId = $result->userId;
        $trip = $result->data;

        $this->api->chat($userId)->sendMessage()->text('confirmTrip', (array)$trip)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('confirm', 'data', 'Trip.submit');
            $m->button('cancel', 'data', 'Main.menu');
        })->exec();
    }

    public function submit($callback)
    {
        $config = config('telegram');
        $channel = $config->channel;
        $userId = $callback->from->id;
        $cache = $callback->cache;
        $messageId = $callback->message->message_id;
        $data = $cache->flow->data;

        if ($config->keywords->desire == $data->ticket) $data->ticket = null;

        $user = User::find($userId);
        (new MyAddressBot)->existsOrStore($userId, $data);

        $trip = $user->trips()->create((array) $data);
        $id = $trip->id;
        $trip->save();

        $trip = $user->trips()->find($id);
        $trip->requirement();

        $result = $this->api->chat('@' . $channel)->sendMessage()->text('channelTrip', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($trip, $config) {
            $m->button('sendFormRequest', 'url', 't.me/' . $config->bot . '?start=trip-' . $trip->id);
        })->exec();

        $this->api->chat($userId)->updateMessage()->text('tripSubmitted')->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) use ($result, $config) {
            $m->button('showInChannel', 'url', 't.me/' . $config->channel . '/' . $result->message_id);
        })->exec();

        $user->trips()->find($id)->update(['messageId' => $result->message_id]);

        $message = new stdClass;
        $message->from = (object)['id' => $userId];
        $main = new MainBot();
        $main->menu($message);
    }

    public function form($message)
    {
        $config = config('telegram');
        $userId = $message->from->id;
        $text = $message->text;

        $package = str_replace('trip-', '', $text);
        $package = Package::find($package);
        $main = new MainBot();
        if ($main->checkLogin($userId)) {
            $transfer = Transfer::where(['package' => $package, 'status' => 'verified']);
            if ($package->user->id == $userId)
                $main->api->chat($userId)->sendMessage()->text('requestSelf')->exec();
            else if ($package->status != "open")
                $main->api->chat($userId)->sendMessage()->text('requestClosed')->exec();
            else if ($transfer->exists())
                $main->api->chat($userId)->sendMessage()->text('requestIsDone')->exec();

            else {
                $main->api->chat($userId)->sendMessage()->text('requestPackageForm', $package)->inlineKeyboard()->rowButtons(function ($m) use ($package) {
                    $m->button('createTrip', 'data', 'Trip.create.' . $package->id);
                    $m->button('selectTrip', 'query', time())->inlineMode('trips');
                })->exec();
                $action = $config->actions->selectTrip;
                $this->api->putCache($userId, 'action', $action);
                $this->api->putCache($userId, 'package', $package->id);
            }
        } else $main->needLogin($userId);
    }

    public function select($message)
    {
        $userId = $message->from->id;
        $messageId = $message->message_id;
        $id = $message->text;

        $this->api->chat($userId)->updateButton()->messageId($messageId - 1)->exec();

        $data = new stdClass;
        $data->userId = $userId;
        $data->trip = $id;
        $data->package = $message->cache->package;

        $this->request($data);
    }

    public function request($data)
    {
        $userId = $data->userId;
        $user = User::find($userId);
        $trip = $user->trips()->find($data->trip);
        $trip->cc();
        $trip->checkRequirment();
        $package = Package::find($data->package);

        $pending = config('telegram')->messages->pending;
        $this->api->chat($userId)->sendMessage()->text('requestPackageSent', $trip,  "\n\n" . $pending)->exec();

        $this->api->chat($package->user->id)->sendMessage()->text('requestPackage', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($data) {
            $data = $data->trip . ',' . $data->package;
            $m->button('acceptRequest', 'data', 'Trip.accept.' . $data);
            $m->button('rejectRequest', 'data', 'Trip.reject.' . $data);
        })->exec();

        Transfer::create([
            'package' => $data->package,
            'trip' => $data->trip,
            'type' => 'tripToPackage',
            'status' => 'pendingPacker'
        ])->save();
    }

    public function reject($callback)
    {
        $config = config('telegram');
        $userId = $callback->from->id;
        $messageId = $callback->message->message_id;
        $text = $callback->message->text;
        $reject = $config->messages->rejectRequest;

        $data = explode(',', $callback->data);

        $trip = Trip::find($data[0]);
        $trip->checkRequirment();
        $trip->cc();
        $package = Package::find($data[1]);
        $transfer = Transfer::where(['package' => $package->id, 'trip' => $trip->id]);

        if (in_array($userId, $config->admins)) {
            $transfer->update(['status' => 'adminRejected']);
            $this->api->chat($userId)->updateMessage()->text(plain: $text . "\n\n" . $reject)->messageId($messageId)->exec();
            $this->api->chat($trip->userId)->sendMessage()->text('requestPackage', $trip, "\n\n" . $reject)->exec();
            $this->api->chat($package->userId)->sendMessage()->text('requestPackage', $trip, "\n\n" . $reject)->exec();
        } else {
            $transfer->update(['status' => 'packerRejected']);
            $text .= "\n\n" . $reject;
            $this->api->chat($userId)->updateMessage()->text(plain: $text)->messageId($messageId)->exec();
            $this->api->chat($trip->userId)->sendMessage()->text(plain: $text)->exec();
        }
    }

    public function accept($callback)
    {
        $config = config('telegram');
        $userId = $callback->from->id;
        $messageId = $callback->message->message_id;
        $text = $callback->message->text;
        $accept = $config->messages->acceptRequest;
        $pending = $config->messages->pendingAdmin;

        $data = explode(',', $callback->data);

        $trip = Trip::find($data[0]);
        $package = Package::find($data[1]);

        $transfer = Transfer::where(['package' => $package->id, 'trip' => $trip->id]);

        if (in_array($userId, $config->admins)) {
            $transfer->update(['status' => 'verified']);
            $text .= "\n\n" . $accept;
            $this->api->chat($userId)->updateMessage()->text(plain: $text)->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m)  use ($package, $trip) {
                $m->button('contactTripper', 'url', 'tg://user?id=' . $trip->userId);
                $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
            })->exec();
            $this->api->chat($package->userId)->sendMessage()->text(plain: $text)->inlineKeyboard()->rowButtons(function ($m)  use ($trip) {
                $m->button('contactTripper', 'url', 'tg://user?id=' . $trip->userId);
            })->exec();
            $this->api->chat($trip->userId)->sendMessage()->text(plain: $text)->inlineKeyboard()->rowButtons(function ($m)  use ($package) {
                $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
            })->exec();

            $channel = $config->channel;
            $this->api->chat('@' . $channel)->updateButton()->inlineKeyboard()->rowButtons(function ($m) use ($channel) {
                $m->button('requestDone', 'url', 't.me/' . $channel);
            })->messageId($package->messageId)->exec();
        } else {
            $transfer->update(['status' => 'pendingAdmin']);
            $text .= "\n\n" . $accept . "\n\n" . $pending;
            $this->api->chat($package->userId)->updateMessage()->text(plain: $text)->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) use ($package) {
                $m->button('closeRequest', 'data', 'Package.status.close,' . $package->id);
            })->exec();
            $this->api->chat($trip->userId)->sendMessage()->text(plain: $text)->exec();

            $trip->checkRequirment();
            foreach ($package->toArray() as $key => $value) $trip->{'package' . ucfirst($key)} = $value;

            foreach ($config->admins as $admin)
                $this->api->chat($admin)->sendMessage()->text('requestTripAdmin', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($data) {
                    $data = implode(',', $data);
                    $m->button('acceptRequest', 'data', 'Trip.accept.' . $data);
                    $m->button('rejectRequest', 'data', 'Trip.reject.' . $data);
                })->rowButtons(function ($m)   use ($package, $trip) {
                    $m->button('contactTripper', 'url', 'tg://user?id=' . $trip->userId);
                    $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
                })->rowButtons(function ($m) use ($trip) {
                    $m->button('contactAndImageDocs', 'data', 'Trip.contactAndImageDocs.' . $trip->id);
                })->exec();
        }
    }

    public function contactAndImageDocs($callback)
    {
        $userId = $callback->from->id;
        $messageId = $callback->message->message_id;

        $trip = Trip::find($callback->data);
        $ticket = $trip->getRawOriginal('ticket');
        $passport = $trip->user->identity->getRawOriginal('passport');
        $contact = $trip->user->contact;

        $paths = new stdClass;
        if (isset($ticket)) $paths->ticket = "tickets/" . $ticket;
        if (isset($passport)) $paths->passport = "passports/" . $passport;
        $paths = (array)$paths;
        if (count($paths) > 0) {
            $this->api->showAlert($callback->id)->text('sentDocs')->exec();
            $count = count($paths);
            $i = 0;
            foreach ($paths as $path) {
                $api = $this->api->chat($userId)->sendPhoto()->photo($path);
                if ($i == 0) $api->reply($messageId);
                if ($i == $count - 1) $api->noreply()->caption('contactInfo', $contact);
                $api->exec();
                $i++;
            };
        } else $this->api->showAlert($callback->id, true)->text('noDocs')->exec();
    }
}
