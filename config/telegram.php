<?php

return json_decode(json_encode([
    "token" => env("BOT_TOKEN"),
    "channel" => "bebarinoTest",
    "bot" => "follow4u_bot",
    // "admins" => [1613366049],
    "admins" => [130912163],
    "support" => 1613366049,
    require './config/bot/actions.php',
    require './config/bot/flows.php',
    require './config/bot/keywords.php',
    require './config/bot/messages.php',
    require './config/bot/optionals.php',
]));
