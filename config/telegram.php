<?php

include('./config/telegram/actions.php');
include('./config/telegram/flows.php');
include('./config/telegram/keywords.php');
include('./config/telegram/messages.php');
include('./config/telegram/optionals.php');

return $actions;
return $flows;
return json_decode(json_encode([
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
]));
