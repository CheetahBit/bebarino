<?php

namespace App\Telegram;

use App\Models\Country;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use stdClass;

class MainBot extends ParentBot
{

    public function start()
    {
        $this->clear();

        $user =  $this->register($this->userId);
        $isLogged = isset($user->phone);

        $menu =  $isLogged ? 'mainMenu' : 'guestMenu';

        $this->api->sendMessage()->text($menu)->keyboard()->rowKeys(function ($m)  use ($isLogged) {
            $m->key($isLogged ? 'account' : 'beginning');
        })->rowKeys(function ($m) use ($isLogged) {
            if ($isLogged) {
                $m->key('submitTrip');
                $m->key('submitPackage');
            }
        })->rowKeys(function ($m) use ($isLogged) {
            if ($isLogged) {
                $m->key('addresses');
                $m->key('cards');
            }
        })->rowKeys(function ($m) {
            $m->key('support');
            $m->key('aboutUs');
        })->exec();

        $this->messageId -= ($this->type == 'message' ? 1 : 0);
        $this->api->chat($this->userId)->updateButton()->messageId($this->messageId)->exec();
    }

    public function beginning()
    {
        if (isset($this->user->phone))
            $this->api->chat($this->userId)->sendMessage()->text('alreadyLogged')->exec();
        else {
            $flow = new FlowBot($this->update);
            $flow->start('beginning', 'setPhone');
        }
    }

    public function setPhone()
    {
        $phone = $this->result->data->contact;
        $this->user->update(['phone' => $phone]);
        $this->user->account()->update(['phone' => $phone]);
        $this->api->sendMessage()->text('loginSuccessfully')->exec();
        $this->start();
    }

    public function support()
    {
        $config = $this->config;
        $this->api->sendMessage()->text('support')->inlineKeyboard()->rowButtons(function ($m) use ($config) {
            $m->button('contactSupport', 'url', 'tg://user?id=' . $config->support);
        })->exec();
    }

    public function aboutUs()
    {
        $this->api->sendMessage()->text('aboutUs')->exec();
    }

    public function submitTrip()
    {
        if (isset($this->user->phone)) {
            $this->api->sendMessage()->text('submitTrip')->inlineKeyboard()->rowButtons(function ($m) {
                $m->button('backward', 'data', 'Main.menu');
            })->exec();
            $flow = new FlowBot($this->update);
            $flow->start('trip', 'confirm', 'submit');
        } else $this->needLogin();
    }

    public function submitPackage()
    {
        if (isset($this->user->phone)) {
            $this->api->sendMessage()->text('submitPackage')->inlineKeyboard()->rowButtons(function ($m) {
                $m->button('backward', 'data', 'Main.menu');
            })->exec();
            $flow = new FlowBot($this->update);
            $flow->start('package', 'confirm', 'submit');
        } else $this->needLogin();
    }

    private function register()
    {
        $user = User::firstOrCreate(['id' => $this->userId]);
        if (!isset($user->account)) 
            $user->account()->create()->save();
        return $user;
    }

    public function needLogin()
    {
        $this->api->sendMessage()->text('needLogin')->exec();
        $this->start();
    }

    static function sendGroupedTripsCards()
    {
        $config = config('telegram');
        $channel = '@' . $config->channel;

        $data = new stdClass;
        $data->country = null;
        $data->trips = '';

        $countries = Country::where('id', '>', 1)->get();
        $trips = Trip::where('messageId', '<>', null)
            ->where('date', '>=', Carbon::today()->format('Y/m/d'))
            ->orderBy('date', 'asc')->get();


        $func = function ($trips, $country) use ($channel) {
            if (count($trips) > 0) {
                $day = null;
                $data = new stdClass;
                $data->country = $country;
                $data->trips = '';
                foreach ($trips as $trip) {
                    if ($day != $trip->date) {
                        $day = $trip->date;
                        $data->trips .= "\n👉" . $day . "\n";
                    }
                    $temp = $trip->fromCity . " به " . $trip->toCity;
                    $data->trips .= "🔸 " . '<a href="t.me/' . $channel . '/' . $trip->messageId . '">' . $temp . '</a>' . "\n";
                }

                if (strlen($data->trips) > 4000) {
                    $i = 0;
                    $temp = explode("\n", $data->trips);
                    while ($i < count($temp)) {
                        $text = '';
                        while (strlen($text) < 4000) $text .= $temp[$i++];
                        $data->trips = $text;
                        $this->api->chat($channel)->sendMessage()->text('tripsGroup', (array)$data)->exec();
                    }
                } else $this->api->chat($channel)->sendMessage()->text('tripsGroup', (array)$data)->exec();
            }
        };

        foreach ($countries as $country) {
            $filtered = $trips->filter(function ($trip,) use ($country) {
                return str_contains($trip->fromCountry, $country->title) ||  str_contains($trip->toCountry, $country->title);
            });
            $func($filtered,  $country->fullTitle());
            $trips = $trips->diff($filtered);
        }
        $func($trips,  'کشورهای دیگر');
    }
}
