<?php

namespace App\Telegram;

use App\Models\Transfer;

class InlineBot extends ParentBot
{

    public function handle()
    {
        $results = match ($this->cache->inline) {
            'addresses' => $this->getAddresses(),
            'cards' => $this->getCards(),
            'packages' => $this->getPackages(),
            'trips' => $this->getTrips()
        };
        $this->api->answerInline($this->inlineId, $results)->exec();
    }

    public function getAddresses()
    {
        $results = [];
        $addresses = $this->user->addresses();
        $flow = $this->cache->flow ?? null;
        if (isset($flow) && ($flow->name == 'trip' || $flow->name == 'package'))
            $addresses->where([
                'country' => $flow->data->toCountry ?? $flow->data->fromCountry,
                'city' => $flow->data->toCity ?? $flow->data->fromCity,
            ]);
        $addresses = $addresses->orderBy('updated_at', 'desc')->get();
        $select = $this->cache->action == 'showAddress';
        foreach ($addresses as $address) {
            $results[] = [
                'type' => 'article',
                'title' => $address->country . " , " . $address->city,
                'description' => $address->address,
                'input_message_content' => ['message_text' => $select ? $address->id : $address->address],
                'id' => $address->id,
            ];
        }
        if ($select) $results[] = [
            'type' => 'article',
            'title' => $this->config->keywords->createAddress,
            'input_message_content' => ['message_text' => 'createAddress'],
            'id' => 'createAddress',
        ];

        return $results;
    }

    public function getCards()
    {
        $results = [];
        $packages = $this->user->packages;
        $trips = $this->user->trips;
        $requests = collect([]);
        $requests = $requests->merge($packages)->merge($trips)->sortByDesc('updated_at');

        foreach ($requests as $request) {
            $type = (isset($request->date) ? 'trip' : 'package');
            $title = $this->config->keywords->{$type} . " - " .  $request->fromCountry . " , " . $request->fromCity . " > " . $request->toCountry . " , " . $request->toCity;
            $results[] = [
                'type' => 'article',
                'title' => $title,
                'description' => ($request->date ?? '') . " " . $request->desc,
                'input_message_content' => ['message_text' => 'show' . ucfirst($type) . "-" . $request->id],
                'id' => $type . $request->id,
            ];
        }

        if (count($results) < 1) $results[] = [
            'type' => 'article',
            'title' => $this->config->messages->cardsNotFound,
            'input_message_content' => ['message_text' => '/start'],
            'id' => 'cardsNotFound',
        ];

        return $results;
    }

    public function getPackages()
    {
        $results = [];
        $packages = $this->user->packages()->get()->reverse()->values();
        foreach ($packages as $package) {
            if (Transfer::where(['package' => $package->id])->exists()) continue;
            $title = $this->config->keywords->package . " - " .  $package->fromCountry . " , " . $package->fromCity . " > " . $package->toCountry . " , " . $package->toCity;
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
            'title' => $this->config->keywords->createPackage,
            'input_message_content' => ['message_text' => 'createPackage'],
            'id' => 'createPackage',
        ];

        return $results;
    }

    public function getTrips()
    {
        $results = [];
        $trips = $this->user->trips()->get()->reverse()->values();
        foreach ($trips as $trip) {
            if (Transfer::where(['trip' => $trip->id])->exists()) continue;
            $title = $this->config->keywords->trip . " - " .  $trip->fromCountry . " , " . $trip->fromCity . " > " . $trip->toCountry . " , " . $trip->toCity;
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
            'title' => $this->config->keywords->createTrip,
            'input_message_content' => ['message_text' => 'createTrip'],
            'id' => 'createTrip',
        ];

        return $results;
    }
}
