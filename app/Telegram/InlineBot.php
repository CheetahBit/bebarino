<?php

namespace App\Telegram;

use App\Models\User;
use App\Telegram\APIBot;
use Illuminate\Support\Facades\Cache;

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

                foreach ($addresses as $address) {
                    $results[] = [
                        'type' => 'article',
                        'title' => $address->country . " , " . $address->city,
                        'description' => $address->address,
                        'input_message_content' => ['message_text' => $address->address],
                        'id' => $address->id,
                    ];
                }
                if (count($results) < 1)
                    $results[] = [
                        'type' => 'article',
                        'title' => $messages->notFound,
                        'id' => 'createAddress',
                    ];
                break;

            case 'requests':
                $packages = $user->packages;
                $trips = $user->trips;
                $requests = $trips->merge($packages)->sortByDesc('updated_at');
                foreach ($requests as $request) {
                    $request->cc();
                    $type = (isset($request->date) ? 'trip' : 'package');

                    $results[] = [
                        'type' => 'article',
                        'title' => $keywords->{$type},
                        'description' => ($request->fromAddress . " > " . $request->toAddress) . "\n" . ($request->date ?? $request->desc),
                        'input_message_content' => ['message_text' => $type . "-" . $request->id],
                        'id' => $request->id,
                    ];
                }
                break;

            case 'packages':
                $packages = $user->packages()->get()->reverse()->values();
                foreach ($packages as $package) {
                    $package->cc();

                    $results[] = [
                        'type' => 'article',
                        'title' => $keywords->package . ($package->fromAddress . " > " . $package->toAddress),
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
                    $trip->cc();

                    $results[] = [
                        'type' => 'article',
                        'title' => $keywords->trip . ($trip->fromAddress . " > " . $trip->toAddress),
                        'description' => $trip->desc,
                        'input_message_content' => ['message_text' =>  $trip->id],
                        'id' => $trip->id,
                    ];
                }
                $results[] = [
                    'type' => 'article',
                    'title' => $keywords->createPackage,
                    'input_message_content' => ['message_text' => 'createPackage'],
                    'id' => 'createPackage',
                ];
                break;
            default:
                break;
        }
        $api = new APIBot();
        $api->answerInline($inlineId, $results)->exec();
    }
}
