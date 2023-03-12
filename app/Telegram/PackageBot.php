<?php

namespace App\Telegram;

use App\Models\Package;
use App\Models\Transfer;
use App\Models\Trip;
use Illuminate\Support\Facades\Log;
use stdClass;

class PackageBot extends ParentBot
{

    public function show()
    {
        if ($this->type = 'callback')
            $this->api->updateButton()->messageId($this->messageId)->exec();

        $id = $this->data ?? $this->cache->package;

        $package = $this->user->packages()->find($id);
        $status = $package->getRawOriginal('status');

        $this->api->sendMessage()->text('packageInfo', $package)->inlineKeyboard()->rowButtons(function ($m) use ($status, $id) {
            if ($status != 'closedByAdmin')
                $m->button('edit', 'data', 'Package.edit.' . $id);
            $m->button('backward', 'data', 'Card.index');
        })->rowButtons(function ($m) use ($status, $id) {
            if ($status != 'closedByAdmin') {
                if ($status == 'closed')
                    $m->button('openRequest', 'data', 'Package.status.opened,' .  $id);
                else
                    $m->button('closeRequest', 'data', 'Package.status.closed,' .  $id);
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
        $flow->start('package','confirm', 'store');
    }

    public function store()
    {
        $data = $this->cache->flow->data;

        $package = $this->user->packages()->create((array)$data);
        $id = $package->id;
        $package->save();

        AddressBot::storeFromToAddress($this->user, $data);

        $this->request($id);
    }

    public function form()
    {
        $trip = explode('-', $this->data)[1];
        $trip = Trip::find($trip);

        if (isset($this->user->phone)) {
            $transfer = Transfer::where('trip', $trip->id);
            $temp = Transfer::where('trip', $trip->id);
            $packages = $this->user->packages()->select('id')->pluck('id')->toArray();

            if ($trip->user->id == $this->userId)
                $this->api->sendMessage()->text('requestIsSelf')->exec();
            else if ($trip->getRawOriginal('status') != "opened")
                $this->api->sendMessage()->text('requestIsClosed')->exec();
            else if ($transfer->whereIn('package', $packages)->exists())
                $this->api->sendMessage()->text('requestAlready')->exec();
            else if ($temp->where('status', 'verified')->exists())
                $this->api->sendMessage()->text('requestIsDone')->exec();
            else {
                $isAdmin = in_array($this->userId, $this->config->admins);
                $this->api->sendMessage()->text('requestTripForm', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($isAdmin, $trip) {
                    if ($isAdmin) {
                        $m->button('delete', 'data', 'Trip.destroy.' .  $trip->id);
                        $m->button('closeRequest', 'data', 'Trip.close.' .  $trip->id);
                        $m->button('contactTripper', 'url', 'tg://user?id=' .  $trip->userId);
                    } else {
                        $m->button('createPackage', 'data', 'Package.create');
                        $m->button('selectPackage', 'query', time());
                    }
                })->exec();

                $this->putCache('action', 'selectPackage');
                $this->putCache('trip', $trip->id);
                $this->putCache('inline', 'packages');
            }
        } else (new MainBot($this->update))->needLogin();
    }

    public function submit()
    {
        $config = $this->config;
        $channel = $config->channel;
        $data = $this->cache->flow->data;

        $package = $this->user->packages()->firstOrCreate((array) $data);

        $result = $this->api->chat('@' . $channel)->sendMessage()->text('channelPackage', $package)->inlineKeyboard()->rowButtons(function ($m) use ($package, $config) {
            $m->button('sendFormRequest', 'url', 't.me/' . $config->bot . '?start=package-' . $package->id);
        })->exec();

        $this->api->chat($this->userId)->updateMessage()->text('packageSubmitted', $package)->inlineKeyboard()->rowButtons(function ($m) use ($result, $channel) {
            $m->button('showInChannel', 'url', 't.me/' . $channel . '/' . $result->message_id);
        })->messageId($this->messageId)->exec();

        $package->update(['messageId' => $result->message_id]);
        AddressBot::storeFromToAddress($this->user, $data);

        (new MainBot($this->update))->start();
    }

    public function edit()
    {
        $id = $this->data;

        $transfer = Transfer::where(['package' => $id])->where(function ($query) {
            return $query->whereIn('status', ['pendingAdmin', 'verified']);
        });

        if ($transfer->exists())
            $this->api->showAlert($this->callbackId)->text('notEditable')->exec();
        else {
            $this->messageId += $this->type == 'message' ? -1 : 0;
            $this->api->updateButton()->inlineKeyboard()->rowButtons(function ($m) use ($id) {
                $m->button('backward', 'data', 'Package.show.' . $id);
            })->messageId($this->messageId)->exec();

            $this->putCache('package', $id);
            $flow = new FlowBot($this->update);
            $flow->start('package','confirm', 'update');
        }
    }

    public function update()
    {
        $config = $this->config;
        $cache = $this->cache;
        $data = $cache->flow->data;
        $id = $cache->package;

        $package = $this->user->packages()->find($id);
        

        $this->api->updateMessage()->text('packageInfo', $package)->messageId($this->messageId)->exec();

        if (isset($package->messageId)) {
            $result = $this->api->chat('@' . $this->config->channel)->updateMessage()->text('channelPackage', $package)->inlineKeyboard()->rowButtons(function ($m) use ($package, $config) {
                $m->button('sendFormRequest', 'url', 't.me/' . $config->bot . '?start=package-' . $package->id);
            })->messageId($package->messageId)->exec();

            if (!isset($result)) {
                $this->api->sendMessage()->exec();
                $this->api->deleteMessage()->messageId($package->messageId)->exec();
                $this->user->packages()->find($package->id)->update(['messageId' => $result->message_id]);
            }
        }

        $this->user->packages()->find($id)->update((array)$data);

        AddressBot::storeFromToAddress($this->user, $data);
    }

    public function destroy()
    {
        $config = $this->config;;
        $channel = $config->channel;
        $deleted = $config->messages->deleted;

        $text = $this->text . "\n\n" . $deleted;

        $package = Package::find($this->data);
        $messageId = $package->messageId;
        $package->delete();

        $this->api->chat($this->userId)->updateMessage()->text(plain: $text)->inlineKeyboard()->rowButtons(function ($m) use ($package) {
            $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
        })->messageId($this->messageId)->exec();

        $this->api->chat($package->user->id)->sendMessage()->text('requestDeletedByAdmin', $package)->exec();

        $this->api->chat('@' . $channel)->deleteMessage()->messageId($messageId)->exec();
    }

    public function status()
    {
        $data = explode(",", $this->data);
        $status = $data[0];
        $packageId = $data[1];

        $package = $this->user->packages()->find($packageId);

        if ($package->getRawOriginal('status') == 'closedByAdmin') {
            $this->api->showAlert($this->callbackId)->text('requestClosedByAdmin', $package)->exec();
            $this->api->updateButton()->inlineKeyboard()->rowButtons(function ($m) {
                $m->button('backward', 'data', 'Card.index');
            })->messageId($this->messageId)->exec();
        } else {
            $this->api->showAlert($this->callbackId)->text('request' . ucfirst($status))->exec();

            $package->update(['status' => $status]);
            $package = $this->user->packages()->find($packageId);

            $transfer = Transfer::where(['package' => $package->id]);
            if ($transfer->exists()) $package->status = $transfer->first()->status;

            $this->api->updateMessage()->text('packageInfo', $package)->inlineKeyboard()->rowButtons(function ($m) {
                $m->button('edit', 'data', 'Package.edit');
                $m->button('backward', 'data', 'Card.index');
            })->rowButtons(function ($m) use ($package) {
                $status = $package->getRawOriginal('status');
                if ($status == 'closed') $m->button('openRequest', 'data', 'Package.status.opened,' .  $package->id);
                else $m->button('closeRequest', 'data', 'Package.status.closed,' .  $package->id);
            })->messageId($this->messageId)->exec();

            

            if (isset($package->messageId)) {
                $config = $this->config;
                $package->status = $status;

                $this->api->chat('@' . $config->channel)->updateMessage()->text('channelPackage', $package)->inlineKeyboard()->rowButtons(function ($m) use ($package, $config) {
                    $status = $package->getRawOriginal('status');
                    if ($status == 'opened')
                        $url = 't.me/' . $config->bot . '?start=package-' . $package->id;
                    else
                        $url = 't.me/' . $config->channel;
                    $m->button('sendFormRequest', 'url', $url);
                })->messageId($package->messageId)->exec();
            }
        }
    }

    public function close()
    {
        $this->api->showAlert($this->callbackId)->text('requestClosed')->exec();

        $package = Package::find($this->data);

        $this->api->updateButton()->inlineKeyboard()->rowButtons(function ($m) use ($package) {
            $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
        })->messageId($this->messageId)->exec();

        $this->api->chat($package->user->id)->sendMessage()->text('requestClosedByAdmin', $package)->exec();

        if (isset($package->messageId)) {
            $config = config('telegram');
            $channel = $config->channel;
            
            $package->status = 'closedByAdmin';
            $this->api->chat('@' . $channel)->updateMessage()->text('channelPackage', $package)->messageId($package->messageId)->exec();
        }

        Package::find($package->id)->update(['status' => 'closedByAdmin']);
    }

    public function confirm()
    {
        $result = $this->result;
        $data = $result->data;
        $target = $result->target;

        $this->api->sendMessage()->text('confirmPackage', (array)$data)->inlineKeyboard()->rowButtons(function ($m) use ($target) {
            $m->button('confirm', 'data', 'Package.' . $target);
            $m->button('cancel', 'data', 'Main.start');
        })->exec();
    }

    public function select()
    {
        $this->api->deleteMessage()->messageId($this->messageId)->exec();
        $this->messageId--;
        $this->request($this->data);
    }

    public function request($id)
    {
        $pending = $this->config->messages->pending;

        $package = $this->user->packages()->find($id);
        
        $trip = Trip::find($this->cache->trip);

        $this->api->updateMessage()->text('requestTripSent', $package, "\n\n" . $pending)->inlineKeyboard()->rowButtons(function ($m) use ($package) {
            $m->button('sendRequestToChannel', 'data', 'Package.sendToChannel.' . $package->id);
        })->messageId($this->messageId)->exec();

        $this->api->chat($trip->user->id)->sendMessage()->text('requestTrip', $package)->inlineKeyboard()->rowButtons(function ($m) use ($trip, $package) {
            $data = $trip->id . ',' . $package->id;
            $m->button('acceptRequest', 'data', 'Package.accept.' . $data);
            $m->button('rejectRequest', 'data', 'Package.reject.' . $data);
        })->exec();

        Transfer::create([
            'package' => $package->id,
            'trip' => $trip->id,
            'type' => 'packageToTrip',
            'status' => 'pendingTripper'
        ])->save();
    }

    public function sendToChannel()
    {
        $config = $this->config;

        $package = $this->user->packages()->find($this->data);

        if (!isset($package->messageId)) {
            $result = $this->api->chat('@' . $config->channel)->sendMessage()->text('channelPackage', $package)->inlineKeyboard()->rowButtons(function ($m) use ($package, $config) {
                $m->button('sendFormRequest', 'url', 't.me/' . $config->bot . '?start=package-' . $package->id);
            })->exec();

            $this->api->chat($this->userId)->updateButton()->inlineKeyboard()->rowButtons(function ($m) use ($result, $config) {
                $m->button('showInChannel', 'url', 't.me/' . $config->channel . '/' . $result->message_id);
            })->messageId($this->messageId)->exec();

            $this->user->packages()->find($package->id)->update(['messageId' => $result->message_id]);
        }
    }

    public function reject()
    {
        $config = $this->config;
        $text = $this->text;

        $reject = $config->messages->rejectRequest;
        $data = explode(',', $this->data);

        $trip = Trip::find($data[0]);
        $package = Package::find($data[1]);
        
        $transfer = Transfer::where(['package' => $package->id, 'trip' => $trip->id]);

        if (in_array($this->userId, $config->admins)) {
            $transfer->update(['status' => 'adminRejected']);
            $this->api->chat($this->userId)->updateMessage()->text(plain: $text . "\n\n" . $reject)->messageId($this->messageId)->exec();
            $this->api->chat($package->userId)->sendMessage()->text('requestTrip', $package, "\n\n" . $reject)->exec();
            $this->api->chat($trip->userId)->sendMessage()->text('requestTrip', $package, "\n\n" . $reject)->exec();
        } else {
            $transfer->update(['status' => 'tripperRejected']);
            $text .= "\n\n" . $reject;
            $this->api->chat($this->userId)->updateMessage()->text(plain: $text)->messageId($this->messageId)->exec();
            $this->api->chat($package->userId)->sendMessage()->text(plain: $text)->exec();
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

        $transfer = Transfer::where(['package' => $package->id, 'trip' => $trip->id]);

        if (in_array($this->userId, $config->admins)) {
            $transfer->update(['status' => 'verified']);
            $text .= "\n\n" . $accept;
            $this->api->chat($this->userId)->updateMessage()->text(plain: $text)->inlineKeyboard()->rowButtons(function ($m)  use ($package, $trip) {
                $m->button('contactTripper', 'url', 'tg://user?id=' . $trip->userId);
                $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
            })->messageId($this->messageId)->exec();

            $this->api->chat($package->userId)->sendMessage()->text(plain: $text)->inlineKeyboard()->rowButtons(function ($m)  use ($trip) {
                $m->button('contactTripper', 'url', 'tg://user?id=' . $trip->userId);
            })->exec();

            $this->api->chat($package->userId)->sendMessage()->text('removeKeyboard')->removeKeyboard()->exec();

            $this->api->chat($trip->userId)->sendMessage()->text(plain: $text)->inlineKeyboard()->rowButtons(function ($m)  use ($package) {
                $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
            })->exec();

            $this->api->chat($trip->userId)->sendMessage()->text('removeKeyboard')->removeKeyboard()->exec();
        } else {
            $transfer->update(['status' => 'pendingAdmin']);
            $text .= "\n\n" . $accept . "\n\n" . $pending;

            $this->api->chat($trip->userId)->updateMessage()->text(plain: $text)->inlineKeyboard()->rowButtons(function ($m)  use ($trip) {
                $m->button('closeRequest', 'data', 'Trip.status.closed,' . $trip->id);
            })->messageId($this->messageId)->exec();

            $this->api->chat($package->userId)->sendMessage()->text(plain: $text)->exec();
            
            foreach ($package->toArray() as $key => $value) $trip->{'package' . ucfirst($key)} = $value;
            $trip->packageCode = $package->code;

            foreach ($config->admins as $admin)
                $this->api->chat($admin)->sendMessage()->text('requestPackageAdmin', $trip)->inlineKeyboard()->rowButtons(function ($m) use ($data) {
                    $data = implode(',', $data);
                    $m->button('acceptRequest', 'data', 'Package.accept.' . $data);
                    $m->button('rejectRequest', 'data', 'Package.reject.' . $data);
                })->rowButtons(function ($m)   use ($trip) {
                    $m->button('tripperDocs', 'data', 'Package.contactAndImageDocs.tripper,' . $trip->id);
                    $m->button('contactTripper', 'url', 'tg://user?id=' . $trip->userId);
                })->rowButtons(function ($m) use ($package) {
                    $m->button('packerDocs', 'data', 'Package.contactAndImageDocs.packer,' . $package->id);
                    $m->button('contactPacker', 'url', 'tg://user?id=' .  $package->userId);
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

        $passport = $user->identity->getRawOriginal('passport');
        $contact = $user->contact;

        $paths = new stdClass;
        if (isset($ticket)) $paths->ticket = "tickets/" . $ticket;
        if (isset($passport)) $paths->passport = "passports/" . $passport;
        $paths = (array)$paths;

        if (count($paths) > 0) {
            $this->api->showAlert($this->callbackId)->text('sentDocs')->exec();
            $count = count($paths);
            $i = 0;
            foreach ($paths as $path) {
                $api = $this->api->sendPhoto()->photo($path);
                if ($i == 0) $api->reply($this->messageId);
                if ($i == $count - 1 && $contact->isFullFill()) $api->noreply()->caption('contactInfo', $contact);
                $api->exec();
                $i++;
            };
        } else $this->api->showAlert($this->callbackId, true)->text('noDocs')->exec();
    }
}
