<?php


return json_decode(json_encode([
    "token" => env("BOT_TOKEN"),
    "channel" => "bebarinoTest",
    "bot" => "follow4u_bot",
    // "admins" => [1613366049],
    "admins" => [1645621910],
    "support" => 1613366049,

    'actions' => config('bot.actions'),
    'flows' => config('bot.flows'),
    'keywords' => config('bot.keywords'),
    'messages' => config('bot.messages'),
    'optionals' => config('bot.optionals'),
]));
