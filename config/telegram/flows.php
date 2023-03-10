<?php

return [
    "beginning" => [
        "class" => "Main",
        "steps" => ["contact"],
    ],
    "identity" => [
        "class" => "Account",
        "steps" => ["username", "fullname", "passport"],
    ],
    "contact" => [
        "class" => "Account",
        "steps" => ["email", "phone", "country", "city", "address"],
    ],
    "bank" => [
        "class" => "Account",
        "steps" => ["bankCountry", "accountName", "accountNumber"],
    ],
    "address" => [
        "class" => "Address",
        "steps" => ["country", "city", "address"],
    ],
    "trip" => [
        "class" => "Trip",
        "steps" => [
            "fromCountry", "fromCity", "fromAddress",
            "toCountry", "toCity", "toAddress",
            "date", "ticket", "weight", "price", "desc"
        ],
    ],
    "package" => [
        "class" => "Package",
        "steps" => [
            "fromCountry", "fromCity", "fromAddress",
            "toCountry", "toCity", "toAddress",
            "desc"
        ],
    ]
];
