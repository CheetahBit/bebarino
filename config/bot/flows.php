<?php

return [
    "beginning" => [
        "class" => "Main",
        "steps" => ["contact"],
    ],
    "identityInfo" => [
        "class" => "Account",
        "steps" => ["username", "fullname", "passport"],
    ],
    "contactInfo" => [
        "class" => "Account",
        "steps" => ["email", "phone", "country", "city", "address"],
    ],
    "bankInfo" => [
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
