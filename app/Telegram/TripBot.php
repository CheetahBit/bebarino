<?php

namespace App\Telegram;

use App\Models\Package;
use App\Models\Transfer;
use App\Models\Trip;
use stdClass;

class TripBot extends ParentBot
{

    public function show()
    {
        if ($this->type = 'callback')
            $this->api->updateButton()->messageId($this->messageId)->exec();

        $id = $this->data ?? $this->cache->package;

        $trip = $this->user->trips()->find($id);
        $status = $trip->getRawOriginal('status');

        $this->api->sendMessage()->text('tripInfo', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($status, $id) {
            if ($status != 'closedByAdmin')
                $m->button('edit', 'data', 'Trip.edit.' . $id);
            $m->button('backward', 'data', 'Card.index');
        })->rowButtons(function ($m) use ($status, $id) {
            if ($status != 'closedByAdmin') {
                if ($status == 'closed')
                    $m->button('openRequest', 'data', 'Trip.status.opened,' .  $id);
                else
                    $m->button('closeRequest', 'data', 'Trip.status.closed,' .  $id);
            }
        })->exec();
    }

    public function create()
    {
        $this->messageId += $this->type == 'message' ? -1 : 0;
        $this->api->updateButton()->messageId($this->messageId)->inlineKeyboard()->rowButtons(function ($m) {
            $m->button('backward', 'data', 'Main.start');
        })->exec();

        $flow = new FlowBot($this->update);
        $flow->start('trip', 'confirm', 'store');
    }

    public function store()
    {
        $data = $this->cache->flow->data;

        $trip = $this->user->trips()->create((array)$data);
        $id = $trip->id;
        $trip->save();

        AddressBot::storeFromToAddress($this->user, $data);

        $this->request($id);
    }

    public function form()
    {
        $package = str_replace('#P','',$this->data);
        if(str_contains($package, ' ')) $package = trim($package) - 1000;
        $package = Package::find($package);

        if (isset($this->user->phone)) {
            $transfer = Transfer::where('package', $package->id);
            $temp = Transfer::where('package', $package->id);
            $trips = $this->user->trips()->select('id')->pluck('id')->toArray();

            if ($package->user->id == $this->userId)
                $this->api->sendMessage()->text('requestIsSelf')->exec();
            else if ($package->getRawOriginal('status') != "opened")
                $this->api->sendMessage()->text('requestIsClosed')->exec();
            else if ($transfer->whereIn('trip', $trips)->exists())
                $this->api->sendMessage()->text('requestAlready')->exec();
            else if ($temp->where('status', 'verified')->exists())
                $this->api->sendMessage()->text('requestIsDone')->exec();
            else {
                $this->clear();
                $isAdmin = in_array($this->userId, $this->config->admins);
                $result = $this->api->sendMessage()->text('requestPackageForm', $package, plain: json_encode($trips))->inlineKeyboard()->rowButtons(function ($m) use ($isAdmin, $package) {
                    if ($isAdmin) {
                        if ($isAdmin) $m->button('delete', 'data', 'Package.delete.' .  $package->id);
                        $m->button('closeRequest', 'data', 'Package.close.' .  $package->id);
                        $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
                    } else {
                        $m->button('createTrip', 'data', 'Trip.create');
                        $m->button('selectTrip', 'query', time());
                    }
                })->exec();
                if (!$isAdmin) {
                    $this->putCache('action', 'selectTrip');
                    $this->putCache('package', $package->id);
                    $this->putCache('messageId', $result->message_id);
                    $this->putCache('inline', 'trips');
                }
            }
        } else (new MainBot($this->update))->needLogin();
    }

    public function submit()
    {
        $config = $this->config;
        $channel = $config->channel;
        $data = $this->cache->flow->data;

        $trip = $this->user->trips()->firstOrCreate((array) $data);

        $result = $this->api->chat('@' . $channel)->sendMessage()->text('channelTrip', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($trip, $config) {
            $m->button('sendFormRequest', 'url', 't.me/' . $config->bot . '?start=#T' . $trip->id);
        })->exec();

        $this->api->chat($this->userId)->updateMessage()->text('tripSubmitted', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($result, $channel) {
            $m->button('showInChannel', 'url', 't.me/' . $channel . '/' . $result->message_id);
        })->messageId($this->messageId)->exec();

        $trip->update(['messageId' => $result->message_id]);
        AddressBot::storeFromToAddress($this->user, $data);

        (new MainBot($this->update))->start();
    }

    public function edit()
    {
        $id = $this->data;

        $transfer = Transfer::where(['trip' => $id])->where(function ($query) {
            return $query->whereIn('status', ['pendingAdmin', 'pendingTripper']);
        });

        if ($transfer->exists())
            $this->api->showAlert($this->callbackId)->text('notEditable')->exec();
        else {
            $this->messageId += $this->type == 'message' ? -1 : 0;
            $this->api->updateButton()->inlineKeyboard()->rowButtons(function ($m) use ($id) {
                $m->button('backward', 'data', 'Trip.show.' . $id);
            })->messageId($this->messageId)->exec();

            $this->putCache('trip', $id);
            $flow = new FlowBot($this->update);
            $flow->start('trip', 'confirm', 'update');
        }
    }

    public function update()
    {
        $config = $this->config;
        $cache = $this->cache;
        $data = $cache->flow->data;
        $id = $cache->trip;

        $trip = $this->user->trips()->find($id);

        $this->api->updateMessage()->text('tripInfo', $trip)->messageId($this->messageId)->exec();

        if (isset($trip->messageId)) {
            $result = $this->api->chat('@' . $this->config->channel)->updateMessage()->text('channelTrip', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($trip, $config) {
                $m->button('sendFormRequest', 'url', 't.me/' . $config->bot . '?start=#T' . $trip->id);
            })->messageId($trip->messageId)->exec();

            if (!isset($result)) {
                $this->api->sendMessage()->exec();
                $this->api->deleteMessage()->messageId($trip->messageId)->exec();
                $this->user->trips()->find($trip->id)->update(['messageId' => $result->message_id]);
            }
        }

        $this->user->trips()->find($id)->update((array)$data);

        AddressBot::storeFromToAddress($this->user, $data);
    }

    public function destroy()
    {
        $config = $this->config;;
        $channel = $config->channel;
        $deleted = $config->messages->deleted;

        $text = $this->text . "\n\n" . $deleted;

        $trip = Trip::find($this->data);
        $messageId = $trip->messageId;
        $trip->delete();

        $this->api->chat($this->userId)->updateMessage()->text(plain: $text)->inlineKeyboard()->rowButtons(function ($m) use ($trip) {
            $m->button('contactTripper', 'url', 'tg://user?id=' .  $trip->userId);
        })->messageId($this->messageId)->exec();

        $this->api->chat($trip->user->id)->sendMessage()->text('requestDeletedByAdmin', $trip)->exec();

        $this->api->chat('@' . $channel)->deleteMessage()->messageId($messageId)->exec();
    }

    public function status()
    {
        $data = explode(",", $this->data);
        $status = $data[0];
        $tripId = $data[1];

        $trip = $this->user->trips()->find($tripId);

        if ($trip->getRawOriginal('status') == 'closedByAdmin') {
            $this->api->showAlert($this->callbackId)->text('requestClosedByAdmin', $trip)->exec();
            $this->api->updateButton()->inlineKeyboard()->rowButtons(function ($m) {
                $m->button('backward', 'data', 'Card.index');
            })->messageId($this->messageId)->exec();
        } else {
            $this->api->showAlert($this->callbackId)->text('request' . ucfirst($status))->exec();

            $trip->update(['status' => $status]);

            $trip = $this->user->trips()->find($tripId);

            $transfer = Transfer::where(['trip' => $trip->id]);
            if ($transfer->exists()) $trip->status = $transfer->first()->status;

            $this->api->chat($this->userId)->updateMessage()->text('tripInfo', $trip)->inlineKeyboard()->rowButtons(function ($m) {
                $m->button('edit', 'data', 'Trip.edit');
                $m->button('backward', 'data', 'Card.index');
            })->rowButtons(function ($m) use ($trip) {
                $status = $trip->getRawOriginal('status');
                if ($status == 'closed') $m->button('openRequest', 'data', 'Trip.status.opened,' .  $trip->id);
                else $m->button('closeRequest', 'data', 'Trip.status.closed,' .  $trip->id);
            })->messageId($this->messageId)->exec();


            if (isset($trip->messageId)) {
                $config = $this->config;
                $trip->status = $status;

                $this->api->chat('@' . $config->channel)->updateMessage()->text('channelTrip', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($trip, $config) {
                    $status = $trip->getRawOriginal('status');
                    if ($status == 'opened')
                        $url = 't.me/' . $config->bot . '?start=#T' . $trip->id;
                    else
                        $url = 't.me/' . $config->channel;
                    $m->button('sendFormRequest', 'url', $url);
                })->messageId($trip->messageId)->exec();
            }
        }
    }

    public function close()
    {
        $this->api->showAlert($this->callbackId)->text('requestClosed')->exec();

        $trip = Trip::find($this->data);

        $this->api->updateButton()->inlineKeyboard()->rowButtons(function ($m) use ($trip) {
            $m->button('contactPacker', 'url', 'tg://user?id=' .  $trip->userId);
        })->messageId($this->messageId)->exec();

        $this->api->chat($trip->user->id)->sendMessage()->text('requestClosedByAdmin', $trip)->exec();

        if (isset($trip->messageId)) {
            $config = config('telegram');
            $channel = $config->channel;

            $trip->status = 'closedByAdmin';
            $this->api->chat('@' . $channel)->updateMessage()->text('channelTrip', $trip)->messageId($trip->messageId)->exec();
        }

        Trip::find($trip->id)->update(['status' => 'closedByAdmin']);
    }

    public function confirm()
    {
        $result = $this->result;
        $data = $result->data;
        $target = $result->target;

        if (!isset($data->ticket)) $data->ticket = $this->config->keywords->notEntered;

        $this->api->sendMessage()->text('confirmTrip', (array)$data)->inlineKeyboard()->rowButtons(function ($m) use ($target) {
            $m->button('confirm', 'data', 'Trip.' . $target);
            $m->button('cancel', 'data', 'Main.start');
        })->exec();
    }

    public function select()
    {
        $this->api->deleteMessage()->messageId($this->messageId)->exec();
        $this->messageId = $this->cache->messageId;
        $this->request($this->data);
    }

    public function request($id)
    {
        $pending = $this->config->messages->pending;

        $trip = $this->user->trips()->find($id);

        $package = Package::find($this->cache->package);

        $this->api->updateMessage()->text('requestPackageSent', $trip, "\n\n" . $pending)->inlineKeyboard()->rowButtons(function ($m) use ($trip) {
            $m->button('sendRequestToChannel', 'data', 'Trip.sendToChannel.' . $trip->id);
        })->messageId($this->messageId)->exec();

        $this->api->chat($package->user->id)->sendMessage()->text('requestPackage', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($package, $trip) {
            $data = $package->id . ',' . $trip->id;
            $m->button('acceptRequest', 'data', 'Package.accept.' . $data);
            $m->button('rejectRequest', 'data', 'Package.reject.' . $data);
        })->exec();

        Transfer::create([
            'trip' => $trip->id,
            'package' => $package->id,
            'type' => 'tripToPackage',
            'status' => 'pendingPacker'
        ])->save();
    }

    public function sendToChannel()
    {
        $config = $this->config;

        $trip = $this->user->trips()->find($this->data);

        if (!isset($trip->messageId)) {
            $result = $this->api->chat('@' . $config->channel)->sendMessage()->text('channelTrip', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($trip, $config) {
                $m->button('sendFormRequest', 'url', 't.me/' . $config->bot . '?start=#T' . $trip->id);
            })->exec();

            $this->api->chat($this->userId)->updateButton()->inlineKeyboard()->rowButtons(function ($m) use ($result, $config) {
                $m->button('showInChannel', 'url', 't.me/' . $config->channel . '/' . $result->message_id);
            })->messageId($this->messageId)->exec();

            $this->user->trips()->find($trip->id)->update(['messageId' => $result->message_id]);
        }
    }

    public function reject()
    {
        $config = $this->config;
        $text = $this->text;

        $reject = $config->messages->rejectRequest;
        $data = explode(',', $this->data);

        $package = Package::find($data[0]);
        $trip = Trip::find($data[1]);

        $transfer = Transfer::where(['trip' => $trip->id, 'package' => $package->id]);

        if (in_array($this->userId, $config->admins)) {
            $transfer->update(['status' => 'adminRejected']);
            $this->api->chat($this->userId)->updateMessage()->text(plain: $text . "\n\n" . $reject)->messageId($this->messageId)->exec();
            $this->api->chat($trip->userId)->sendMessage()->text('requestPackage', $trip, "\n\n" . $reject)->exec();
            $this->api->chat($package->userId)->sendMessage()->text('requestPackage', $trip, "\n\n" . $reject)->exec();
        } else {
            $transfer->update(['status' => 'packerRejected']);
            $text .= "\n\n" . $reject;
            $this->api->chat($this->userId)->updateMessage()->text(plain: $text)->messageId($this->messageId)->exec();
            $this->api->chat($trip->userId)->sendMessage()->text(plain: $text)->exec();
        }
    }

    public function accept()
    {
        $config = $this->config;
        $text = $this->text;

        $accept = $config->messages->acceptRequest;
        $pending = $config->messages->pendingAdmin;

        $data = explode(',', $this->data);

        $trip = Trip::find($data[0]);
        $package = Package::find($data[1]);

        $transfer = Transfer::where(['trip' => $trip->id, 'package' => $package->id]);

        if (in_array($this->userId, $config->admins)) {
            $transfer->update(['status' => 'verified']);
            $text .= "\n\n" . $accept;
            $this->api->chat($this->userId)->updateMessage()->text(plain: $text)->inlineKeyboard()->rowButtons(function ($m)  use ($trip, $package) {
                $m->button('contactPacker', 'url', 'tg://user?id=' . $package->userId);
                $m->button('contactPacker', 'url', 'tg://user?id=' .  $trip->userId);
            })->messageId($this->messageId)->exec();

            $this->api->chat($trip->userId)->sendMessage()->text(plain: $text)->inlineKeyboard()->rowButtons(function ($m)  use ($package) {
                $m->button('contactPacker', 'url', 'tg://user?id=' . $package->userId);
            })->exec();

            $this->api->chat($trip->userId)->sendMessage()->text('removeKeyboard')->removeKeyboard()->exec();

            $this->api->chat($package->userId)->sendMessage()->text(plain: $text)->inlineKeyboard()->rowButtons(function ($m)  use ($trip) {
                $m->button('contactPacker', 'url', 'tg://user?id=' .  $trip->userId);
            })->exec();

            $this->api->chat($package->userId)->sendMessage()->text('removeKeyboard')->removeKeyboard()->exec();
        } else {
            $transfer->update(['status' => 'pendingAdmin']);
            $text .= "\n\n" . $accept . "\n\n" . $pending;

            $this->api->chat($trip->userId)->updateMessage()->text(plain: $text)->inlineKeyboard()->rowButtons(function ($m)  use ($package) {
                $m->button('closeRequest', 'data', 'Package.status.closed,' . $package->id);
            })->messageId($this->messageId)->exec();

            $this->api->chat($package->userId)->sendMessage()->text(plain: $text)->exec();

            foreach ($trip->toArray() as $key => $value)
                $package->{'trip' . ucfirst($key)} = $value;
            $package->tripCode = $trip->code;
            $package->tripHasPassport = $trip->hasPassport;
            $package->tripHasContact = $trip->hasContact;
            $package->tripHasTicket = $trip->hasTicket;

            foreach ($config->admins as $admin)
                $this->api->chat($admin)->sendMessage()->text('requestTripAdmin', $package)->inlineKeyboard()->rowButtons(function ($m) use ($data) {
                    $data = implode(',', $data);
                    $m->button('acceptRequest', 'data', 'Trip.accept.' . $data);
                    $m->button('rejectRequest', 'data', 'Trip.reject.' . $data);
                })->rowButtons(function ($m)   use ($package) {
                    $m->button('packerDocs', 'data', 'Trip.contactAndImageDocs.packer,' . $package->id);
                    $m->button('contactPacker', 'url', 'tg://user?id=' . $package->userId);
                })->rowButtons(function ($m) use ($trip) {
                    $m->button('tripperDocs', 'data', 'Trip.contactAndImageDocs.tripper,' . $trip->id);
                    $m->button('contactTripper', 'url', 'tg://user?id=' .  $trip->userId);
                })->exec();
        }
    }

    public function contactAndImageDocs()
    {
        $data = explode(',', $this->data);
        $id = $data[1];

        $ticket = null;

        if ($data[0] == 'packer') {
            $user = Package::find($id)->user;
        } else if ($data[0] == 'tripper') {
            $trip = Trip::find($id);

            $user = $trip->user;
            $ticket = $trip->getRawOriginal('ticket');
        }

        $account = $user->account;

        $paths = new stdClass;
        if (isset($ticket)) $paths->ticket = "tickets/" . $ticket;
        if ($account->hasContact() == "✅") $paths->passport = "passports/" . $account->getRawOriginal('passport');
        $paths = (array)$paths;

        if (count($paths) > 0) {
            $this->api->showAlert($this->callbackId)->text('sentDocs')->exec();
            $count = count($paths);
            $i = 0;
            foreach ($paths as $path) {
                $api = $this->api->chat($this->userId)->sendPhoto()->photo($path);
                if ($i == 0) $api->reply($this->messageId);
                if ($i == $count - 1 && $account->hasContact() == "✅")
                    $api->noreply()->caption('contactInfo', $account);
                $api->exec();
                $i++;
            };
        } else $this->api->showAlert($this->callbackId, true)->text('noDocs')->exec();
    }
}
