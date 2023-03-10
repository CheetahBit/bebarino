<?php

require './bot/actions.php';
require './bot/flows.php';
require './bot/keywords.php';
require './bot/messages.php';
require './bot/optionals.php';


return collect([
    "token" => env("BOT_TOKEN"),
    "channel" => "bebarinoTest",
    "bot" => "follow4u_bot",
    // "admins" => [1613366049],
    "admins" => [130912163],
    "support" => 1613366049,
    
    "actions" => $actions,
    "flows" => $flows,
    "keywords" => $keywords,
    "messages" => $messages,
    "optionals" => $optionals,
]);
