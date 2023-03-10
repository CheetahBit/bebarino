<?php




return json_decode(json_encode([
    "token" => env("BOT_TOKEN"),
    "channel" => "bebarinoTest",
    "bot" => "follow4u_bot",
    // "admins" => [1613366049],
    "admins" => [130912163],
    "support" => 1613366049,
    require './config/telegram/actions.php',
    require './config/telegram/flows.php',
    require './config/telegram/keywords.php',
    require './config/telegram/messages.php',
    require './config/telegram/optionals.php',

]));
