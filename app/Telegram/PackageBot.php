<?php

namespace App\Telegram;

use App\Models\Package;
use App\Models\Transfer;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
            $m->button('backward', 'data', 'MyRequest.index');
        })->exec();

        $this->api->putCache($userId, 'package', $id);
    }

    public function edit($callback)
    {
        $userId = $callback->from->id;
        $flow = new FlowBot();
        $flow->start($userId, 'package', 'Package', 'update', 'show');
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
        $userId = $callback->from->id;
        $messageId =  $callback->message->message_id ?? $callback->message_id - 1;

        $flow = new FlowBot();
        $flow->start($userId, 'package', 'Package', 'store', 'form');

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

        $package = $user->packages()->create((array)$data);
        $id = $package->id;
        $package->save();

        $temp = new stdClass;
        $temp->userId = $userId;
        $temp->package = $id;
        $temp->trip = $this->api->getCache($userId)->trip;

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

        $package = User::find($userId)->packages()->find($cache->package);
        $messageId = $package->messageId;
        $package->delete();
        $this->api->chat('@' . $channel)->deleteMessage()->messageId($messageId)->exec();
        $messageId = $callback->message->message_id;
        $this->api->chat($userId)->updateMessage()->text(plain: $text)->messageId($messageId)->exec();

        (new MyRequestBot())->index($callback);
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
        $userId = $callback->from->id;
        $trip = $callback->data;

        $main = new MainBot();
        $main->api->deleteCache($userId);

        if ($main->checkLogin($userId)) {
            $text = $callback->message->text;
            $messageId = $callback->message->message_id;
            $transfer = Transfer::where(['trip' => $trip, 'status' => 'done']);

            if (Trip::find($trip)->user->id == $userId)
                $main->api->showAlert($callback->id, true)->text('requestSelf')->exec();
            else if ($transfer->exists()) {
                $main->api->showAlert($callback->id, true)->text('requestIsDone')->exec();
                $channel = $config->channel;
                $this->api->chat('@' . $channel)->updateButton()->inlineKeyboard()->rowButtons(function ($m) use ($channel) {
                    $m->button('requestDone', 'url', 't.me/' . $channel);
                })->messageId($messageId);
            } else {
                $main->api->showAlert($callback->id, true)->text('requestFormSent')->exec();
                $main->api->chat($userId)->sendMessage()->text(key: 'requestTripForm', plain: "\n\n" . $text)
                    ->inlineKeyboard()->rowButtons(function ($m) {
                        $m->button('createPackage', 'data', 'Package.create');
                        $m->button('selectPackage', 'query', time())->inlineMode('packages');
                    })->exec();
                $action = $config->actions->selectPackage;
                $this->api->putCache($userId, 'action', $action);
                $this->api->putCache($userId, 'trip', $trip);
            }
        } else $main->needLogin($userId);

        $trip = Trip::find($trip);
        $trip->checkRequirment();
        $trip->cc();

        $this->api->chat('@' . $channel)->sendMessage()->text('channelTrip', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($trip, $channel) {
            $transfer = Transfer::where(['trip' => $trip->id, 'status' => 'verified']);
            if ($transfer->exists()) $m->button('requestDone', 'url', 't.me/' . $channel);
            else $m->button('sendFormRequest', 'data', 'Package.form.' . $trip->id);
        })->exec();
    }

    public function select($message)
    {
        $userId = $message->from->id;
        $messageId = $message->message_id;
        $id = $message->text;

        $this->api->chat($userId)->updateButton()->messageId($messageId - 1)->exec();

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
        $package->cc();
        $trip = Trip::find($data->trip);

        $pending = config('telegram')->messages->pending;
        $this->api->chat($userId)->sendMessage()->text('requestTripSent', $package, "\n\n" . $pending)->exec();

        $this->api->chat($trip->user->id)->sendMessage()->text('requestTrip', $package)->inlineKeyboard()->rowButtons(function ($m) use ($data) {
            $data = $data->trip . ',' . $data->package;
            $m->button('acceptRequest', 'data', 'Package.accept.' . $data);
            $m->button('rejectRequest', 'data', 'Package.reject.' . $data);
        })->exec();

        Transfer::create([
            'package' => $data->package,
            'trip' => $data->trip,
            'type' => 'packageToTrip',
            'status' => 'pendingTripper'
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
        $package = Package::find($data[1]);
        $transfer = Transfer::where(['package' => $package->id, 'trip' => $trip->id]);

        if (in_array($userId, $config->admins)) {
            $transfer->update(['status' => 'adminRejected']);
            $this->api->chat($userId)->updateMessage()->text(plain: $text . "\n\n" . $reject)->messageId($messageId)->exec();
            $this->api->chat($package->userId)->sendMessage()->text('requestTrip', $package, "\n\n" . $reject)->exec();
            $this->api->chat($trip->userId)->sendMessage()->text('requestTrip', $package, "\n\n" . $reject)->exec();
        } else {
            $transfer->update(['status' => 'tripperRejected']);
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
        $trip = Trip::find($data[0]);

        $package = Package::find($data[1]);
        $messageId = $callback->message->message_id;
        $id = $callback->id;
        $text = $callback->message->text;
        $accept = $config->messages->acceptRequest;
        $pending = $config->messages->pendingAdmin;
        $transfer = Transfer::where(['package' => $package->id, 'trip' => $trip->id]);

        if (in_array($userId, $config->admins)) {
            $user = User::find($trip->userId);
            $ticket = $trip->getRawOriginal('ticket');
            $passport = $user->identity->getRawOriginal('passport');
            $contact = $user->contact->isFullFill();

            if (!isset($ticket)) $this->api->showAlert($id, true)->text('noTicket')->exec();
            else if (!isset($passport)) $this->api->showAlert($id, true)->text('noPassport')->exec();
            else if (!$contact) $this->api->showAlert($id, true)->text('noContact')->exec();
            else {
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
                })->messageId($trip->messageId)->exec();
            }
        } else {
            $transfer->update(['status' => 'pendingAdmin']);
            $text .= "\n\n" . $accept . "\n\n" . $pending;
            $this->api->chat($trip->userId)->updateMessage()->text(plain: $text)->messageId($messageId)->exec();
            $this->api->chat($package->userId)->sendMessage()->text(plain: $text)->exec();

            $trip->checkRequirment();
            foreach ($package->toArray() as $key => $value) $trip->{'package' . ucfirst($key)} = $value;

            foreach ($config->admins as $admin)
                $this->api->chat($admin)->sendMessage()->text('requestPackageAdmin', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($data) {
                    $data = implode(',', $data);
                    $m->button('acceptRequest', 'data', 'Package.accept.' . $data);
                    $m->button('rejectRequest', 'data', 'Package.reject.' . $data);
                })->rowButtons(function ($m)   use ($package, $trip) {
                    $m->button('contactTripper', 'url', 'tg://user?id=' . $trip->userId);
                    $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
                })->rowButtons(function ($m) use ($trip) {
                    $m->button('contactAndImageDocs', 'data', 'Package.contactAndImageDocs.' . $trip->id);
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
