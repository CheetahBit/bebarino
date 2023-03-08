<?php

namespace App\Telegram;

use App\Models\Transfer;
use App\Models\User;
use App\Telegram\APIBot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InlineBot
{

    static function handle($inline)
    {
        $id = $inline->from->id;
        $inlineId = $inline->id;
        $config = config('telegram');
        $keywords = $config->keywords;
        $messages = $config->messages;

        $cache = $inline->cache;

        $user = User::find($id);
        $results = [];
        switch ($cache->inline) {
            case 'addresses':
                $addresses = $user->addresses();
                $flow = $cache->flow ?? null;
                if (isset($flow) && ($flow->name == 'trip' || $flow->name == 'package'))
                    $addresses->where([
                        'country' => $flow->data->toCountry ?? $flow->data->fromCountry,
                        'city' => $flow->data->toCity ?? $flow->data->fromCity,
                    ]);
                $addresses = $addresses->orderBy('updated_at', 'desc')->get();
                $select = $cache->action->class == 'MyAddress';
                foreach ($addresses as $address) {
                    $results[] = [
                        'type' => 'article',
                        'title' => $address->country . " , " . $address->city,
                        'description' => $address->address,
                        'input_message_content' => ['message_text' => $select ? $address->id : $address->address],
                        'id' => $address->id,
                    ];
                }
                break;

            case 'requests':
                $packages = $user->packages;
                $trips = $user->trips;
                $requests = $packages->merge($trips)->sortByDesc('updated_at');
                foreach ($requests as $request) {
                    $type = (isset($request->date) ? 'trip' : 'package');
                    $title = $keywords->{$type} . " - " .  $request->fromCountry . " , " . $request->fromCity . " > " . $request->toCountry . " , " . $request->toCity;
                    $results[] = [
                        'type' => 'article',
                        'title' => $title,
                        'description' => ($request->date ?? '') . " " . $request->desc,
                        'input_message_content' => ['message_text' => 'show' . ucfirst($type) . "-" . $request->id],
                        'id' => $type . $request->id,
                        'packages' => $packages->toArray(),
                        'trips' => $trips->toArray(),

                    ];
                }
                break;

            case 'packages':
                $packages = $user->packages()->get()->reverse()->values();
                foreach ($packages as $package) {
                    if (Transfer::where(['package' => $package->id])->exists()) continue;
                    $title = $keywords->package . " - " .  $package->fromCountry . " , " . $package->fromCity . " > " . $package->toCountry . " , " . $package->toCity;
                    $results[] = [
                        'type' => 'article',
                        'title' => $title,
                        'description' => $package->desc,
                        'input_message_content' => ['message_text' =>  $package->id],
                        'id' => $package->id,
                    ];
                }
                $results[] = [
                    'type' => 'article',
                    'title' => $keywords->createPackage,
                    'input_message_content' => ['message_text' => 'createPackage'],
                    'id' => 'createPackage',
                ];
                break;
            case 'trips':
                $trips = $user->trips()->get()->reverse()->values();
                foreach ($trips as $trip) {
                    if (Transfer::where(['trip' => $trip->id])->exists()) continue;
                    $title = $keywords->trip . " - " .  $trip->fromCountry . " , " . $trip->fromCity . " > " . $trip->toCountry . " , " . $trip->toCity;
                    $results[] = [
                        'type' => 'article',
                        'title' => $title,
                        'description' => $trip->date . '  ' . $trip->desc,
                        'input_message_content' => ['message_text' =>  $trip->id],
                        'id' => $trip->id,
                    ];
                }
                $results[] = [
                    'type' => 'article',
                    'title' => $keywords->createTrip,
                    'input_message_content' => ['message_text' => 'createTrip'],
                    'id' => 'createTrip',
                ];
                break;
            default:
                break;
        }
        $api = new APIBot();
        $api->answerInline($inlineId, $results)->exec();
    }
}
