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

        $cache = json_decode(Cache::store('database')->get($inline->from->id, '{}'));

        $user = User::find($id);
        $results = [];
        switch ($cache->inline) {
            case 'addresses':
                foreach ($user->addresses()->get()->reverse()->values() as $address) {
                    $results[] = [
                        'type' => 'article',
                        'title' => $address->country . " , " . $address->city,
                        'description' => $address->address,
                        'input_message_content' => ['message_text' => $address->id],
                        'id' => $address->id,
                    ];
                }
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
                        'description' => ($request->fromAddress . " > " . $request->toAddress) . $request->date ?? $request->desc,
                        'input_message_content' => ['message_text' => $type . "-" . $request->id],
                        'id' => $request->id,
                    ];
                }
                break;
            default:
                break;
        }
        $api = new APIBot();
        $api->answerInline($inlineId, $results)->exec();
    }
}
