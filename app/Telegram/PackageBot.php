<?php

namespace App\Telegram;

use App\Models\Package;
use App\Models\Transfer;
use App\Models\Trip;
use App\Models\User;
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
        $config = config('telegram');
        $userId = $message->from->id;
        $id = $message->text ?? $message->cache->package;
        $isAdmin = in_array($userId, $config->admins);

        if (!isset($message->text)) $this->api->chat($userId)->updateButton()->messageId($message->message->message_id)->exec();

        $package = User::find($userId)->packages()->find($id);
        $this->api->chat($userId)->sendMessage()->text('packageInfo', $package)->inlineKeyboard()->rowButtons(function ($m) use ($package, $isAdmin) {
            if ($isAdmin) {
                $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
                $m->button('closeRequest', 'data', 'Package.close' .  $package->id);
            } else {
                $m->button('delete', 'data', 'Package.delete');
                $m->button('edit', 'data', 'Package.edit');
                $m->button('backward', 'data', 'MyRequest.index');
            }
        })->rowButtons(function ($m) use ($package, $isAdmin) {
            if (!$isAdmin) {
                if ($package->getRawOriginal('status') == 'closed') $m->button('openRequest', 'data', 'Package.status.opened,' .  $package->id);
                else $m->button('closeRequest', 'data', 'Package.status.closed,' .  $package->id);
            }
        })->exec();

        $this->api->putCache($userId, 'package', $id);
    }

    public function status($callback)
    {
        $data = explode(",", $callback->data);
        $status = $data[0];
        $package = $data[1];
        $userId = $callback->from->id;
        $messageId = $callback->message->message_id;
        $id = $callback->id;

        $this->api->showAlert($id)->text('request' . ucfirst($status))->exec();

        $user = User::find($userId);
        $user->packages()->find($package)->update(['status' => $status]);

        $package = $user->packages()->find($package);

        $transfer = Transfer::where(['package' => $package->id]);
        if($transfer->exists()) $package->status = $transfer->first()->status;

        $this->api->chat($userId)->updateMessage()->text('packageInfo', $package)->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) use ($package) {
            $m->button('delete', 'data', 'Package.delete');
            $m->button('edit', 'data', 'Package.edit');
            $m->button('backward', 'data', 'MyRequest.index');
        })->rowButtons(function ($m) use ($package) {
            if ($package->getRawOriginal('status') == 'closed') $m->button('openRequest', 'data', 'Package.status.opened,' .  $package->id);
            else $m->button('closeRequest', 'data', 'Package.status.closed,' .  $package->id);
        })->exec();

        $package->requirement();

        if (isset($package->messageId)) {
            $config = config('telegram');
            $channel = $config->channel;
            $package->statues = $status;
            $this->api->chat('@' . $channel)->updateMessage()->text('channelPackage', $package)->messageId($package->messageId)->inlineKeyboard()->rowButtons(function ($m) use ($package, $config) {
                if ($package->getRawOriginal('status') == 'opened') $url = 't.me/' . $config->bot . '?start=package-' . $package->id;
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
        $package = Package::find($data);
        $this->api->chat($userId)->updateButton()->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) use ($package) {
            $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
        })->exec();
        $this->api->chat($package->user->id)->sendMessage()->text('requestClosedByAdmin', $package->id)->exec();

        if (isset($package->messageId)) {
            $config = config('telegram');
            $channel = $config->channel;
            $package->statues = 'closedByAdmin';
            $this->api->chat('@' . $channel)->updateButton()->text('channelPackage', $package)->messageId($package->messageId)->inlineKeyboard()->rowButtons(function ($m) use ($package, $config) {
                if ($package->status == 'closed') $url = 't.me/' . $config->bot . '?start=package-' . $package->id;
                else $url = 't.me/' . $config->channel;
                $m->button('sendFormRequest', 'url', $url);
            })->exec();
        }

        $package->update(['status' => 'closedByAdmin']);
    }

    public function edit($callback)
    {
        $userId = $callback->from->id;
        $messageId =  $callback->message->message_id ?? $callback->message_id - 1;

        $flow = new FlowBot();
        $flow->start($userId, 'package', 'Package', 'confirmUpdate', 'show');

        $this->api->chat($userId)->updateButton()->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('backward', 'data', 'Package.show');
        })->exec();
    }

    public function confirmUpdate($result)
    {
        $userId = $result->userId;
        $package = $result->data;

        $this->api->chat($userId)->sendMessage()->text('confirmPackage', (array)$package)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('confirm', 'data', 'Package.update');
            $m->button('cancel', 'data', 'Main.menu');
        })->exec();
    }

    public function update($callback)
    {
        $config = config('telegram');
        $channel = $config->channel;
        $userId = $callback->from->id;
        $cache = $callback->cache;
        $data = $cache->flow->data;
        $id = $this->api->getCache($userId)->package;

        $user = User::find($userId);
        (new MyAddressBot)->existsOrStore($userId, $data);

        $package = $user->packages()->find($id);
        $package->update((array)$data);

        $message = (object)["from" => (object)["id" => $userId], "text" => $id];
        $this->show($message);

        $package = $user->packages()->find($id);
        $package->requirement();

        $messageId = $package->messageId;
        if (isset($messageId)) {
            $result = $this->api->chat('@' . $channel)->updateMessage()->text('channelPackage', $package)->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) use ($package, $config) {
                $m->button('sendFormRequest', 'url', 't.me/' . $config->bot . '?start=package-' . $package->id);
            })->exec();

            if (!isset($result)) {
                $result = $this->api->sendMessage()->exec();
                $this->api->deleteMessage()->messageId($messageId)->exec();
                $user->packages()->find($package->id)->update(['messageId' => $result->message_id]);
            }
        }
    }

    public function create($callback)
    {
        $userId = $callback->from->id;
        $messageId =  $callback->message->message_id ?? $callback->message_id - 1;

        $flow = new FlowBot();
        $flow->start($userId, 'package', 'Package', 'confirmStore', 'form');

        $this->api->chat($userId)->updateButton()->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('backward', 'data', 'Main.menu');
        })->exec();
    }

    public function confirmStore($result)
    {
        $userId = $result->userId;
        $package = $result->data;

        $this->api->chat($userId)->sendMessage()->text('confirmPackage', (array)$package)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('confirm', 'data', 'Package.store');
            $m->button('cancel', 'data', 'Main.menu');
        })->exec();
    }

    public function store($callback)
    {
        $userId = $callback->from->id;
        $messageId = $callback->message->message_id;
        $cache = $callback->cache;
        $data = $cache->flow->data;

        $user = User::find($userId);
        (new MyAddressBot)->existsOrStore($userId, $data);
        
        $package = $user->packages()->create((array)$data);
        $id = $package->id;
        $package->save();

        $temp = new stdClass;
        $temp->userId = $userId;
        $temp->messageId = $messageId;
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

    public function confirmSubmit($result)
    {
        $userId = $result->userId;
        $package = $result->data;

        $this->api->chat($userId)->sendMessage()->text('confirmPackage', (array)$package)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('confirm', 'data', 'Package.submit');
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

        $user = User::find($userId);
        (new MyAddressBot)->existsOrStore($userId, $data);

        $package = $user->packages()->create((array) $data);
        $id = $package->id;
        $package->save();

        $package = $user->packages()->find($id);
        $package->requirement();

        $result = $this->api->chat('@' . $channel)->sendMessage()->text('channelPackage', $package)->inlineKeyboard()->rowButtons(function ($m) use ($package, $config) {
            $m->button('sendFormRequest', 'url', 't.me/' . $config->bot . '?start=package-' . $package->id);
        })->exec();

        $this->api->chat($userId)->updateMessage()->text('packageSubmitted', $package)->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m) use ($result, $config) {
            $m->button('showInChannel', 'url', 't.me/' . $config->channel . '/' . $result->message_id);
        })->exec();

        $user->packages()->find($id)->update(['messageId' => $result->message_id]);

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

        $trip = explode('-', $text)[1];
        $trip = Trip::find($trip);
        $main = new MainBot();
        if ($main->checkLogin($userId)) {
            $transfer = Transfer::where(['trip' => $trip, 'status' => 'verified']);
            if ($trip->user->id == $userId)
                $main->api->chat($userId)->sendMessage()->text('requestIsSelf')->exec();
            else if ($trip->getRawOriginal('status') != "opened")
                $main->api->chat($userId)->sendMessage()->text('requestIsClosed')->exec();
            else if ($transfer->exists())
                $main->api->chat($userId)->sendMessage()->text('requestIsDone')->exec();

            else {
                $trip->requirement();
                $main->api->chat($userId)->sendMessage()->text('requestTripForm', $trip)->inlineKeyboard()->rowButtons(function ($m) {
                    $m->button('createPackage', 'data', 'Package.create');
                    $m->button('selectPackage', 'query', time())->inlineMode('packages');
                })->exec();
                $action = $config->actions->selectPackage;
                $this->api->putCache($userId, 'action', $action);
                $this->api->putCache($userId, 'trip', $trip->id);
            }
        } else $main->needLogin($userId);
    }

    public function select($message)
    {
        $userId = $message->from->id;
        $messageId = $message->message_id;
        $id = $message->text;

        $data = new stdClass;
        $data->userId = $userId;
        $data->messageId = $messageId - 1;
        $data->package = $id;
        $data->trip = $message->cache->trip;

        $this->request($data);
    }

    public function request($data)
    {
        $userId = $data->userId;
        $messageId = $data->messageId;
        $user = User::find($userId);
        $package = $user->packages()->find($data->package);
        $package->requirement();

        $trip = Trip::find($data->trip);

        $pending = config('telegram')->messages->pending;
        $this->api->chat($userId)->updateMessage()->text('requestTripSent', $package, "\n\n" . $pending)->messageId($messageId)->exec();

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
        $package->requirement();
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
            })->messageId($trip->messageId)->exec();
        } else {
            $transfer->update(['status' => 'pendingAdmin']);
            $text .= "\n\n" . $accept . "\n\n" . $pending;
            $this->api->chat($trip->userId)->updateMessage()->text(plain: $text)->messageId($messageId)->inlineKeyboard()->rowButtons(function ($m)  use ($trip) {
                $m->button('closeRequest', 'data', 'Trip.status.closed,' . $trip->id);
            })->exec();
            $this->api->chat($package->userId)->sendMessage()->text(plain: $text)->exec();

            $trip->requirement();
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
