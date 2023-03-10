<?php

include 'bot/actions.php';
include 'bot/flows.php';
include 'bot/keywords.php';
include 'bot/messages.php';
include 'bot/optionals.php';


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
