<?php

return [
    "/start" => ["class" => "Main", "method" => "start"],
    "backward" => ["class" => "Main", "method" => "start"],
    "beginning" => ["class" => "Main", "method" => "beginning"],
    "support" => ["class" => "Main", "method" => "support"],
    "aboutUs" => ["class" => "Main", "method" => "aboutUs"],

    "account" => ["class" => "Account", "method" => "index"],
    "identityInfo" => ["class" => "Account", "method" => "show"],
    "bankInfo" => ["class" => "Account", "method" => "show"],
    "contactInfo" => ["class" => "Account", "method" => "show"],

    "submitTrip" => ["class" => "Main", "method" => "submitTrip"],
    "submitPackage" => ["class" => "Main", "method" => "submitPackage"],

    "requestTrip" => ["class" => "Package", "method" => "form"],
    "requestPackage" => ["class" => "Trip", "method" => "form"],

    "addresses" => ["class" => "Address", "method" => "index"],
    "showAddress" => ["class" => "Address", "method" => "show"],
    "createAddress" => ["class" => "Address", "method" => "create"],

    "cards" => ["class" => "Card", "method" => "index"],
    "showCard" => ["class" => "Card", "method" => "show"],

    "flow" => ["class" => "Flow", "method" => "input"],

    "selectPackage" => ["class" => "Package", "method" => "select"],
    "createPackage" => ["class" => "Package", "method" => "create"],

    "selectTrip" => ["class" => "Trip", "method" => "select"],
    "createTrip" => ["class" => "Trip", "method" => "create"],
];
