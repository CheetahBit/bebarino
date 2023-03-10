<?php

require './config/telegram/actions.php';
require './config/telegram/flows.php';
require './config/telegram/keywords.php';
require './config/telegram/messages.php';
require './config/telegram/optionals.php';


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
