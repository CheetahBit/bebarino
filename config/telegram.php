<?php

return json_decode(json_encode([
    "token" => env("BOT_TOKEN"),
    "channel" => "bebarinoTest",
    "admins" => [],
    "flows" => [
        "beginning" => [
            "contact"
        ],
        "identity" => [
            "username", "firstname", "lastname", "passport"
        ],
        "contact" => [
            "email", "phone", "country", "city", "address"
        ],
        "bank" => [
            "country", "accountNumber", "accountName"
        ],
        "address" => [
            "country", "city", "address"
        ],
        "travel" => [
            "fromAddress", "toAddress", "date", "ticket", "weight", "price", "desc"
        ],
        "package" => [
            "fromAddress", "toAddress", "desc"
        ]
    ],
    "actions" => [
        "/start" => ["class" => "Main", "method" => "menu"],
        "beginning" => ["class" => "Main", "method" => "beginning"],
        "support" => ["class" => "Main", "method" => "support"],
        "aboutUs" => ["class" => "Main", "method" => "aboutUs"],

        "account" => ["class" => "Account", "method" => "index"],
        "identity" => ["class" => "Account", "method" => "identity"],
        "bank" => ["class" => "Account", "method" => "bank"],
        "contact" => ["class" => "Account", "method" => "contact"],

        "submitTravel" => ["class" => "Main", "method" => "submitTravel"],
        "submitPackage" => ["class" => "Main", "method" => "submitPackage"],

        "myAddresses" => ["class" => "MyAddress", "method" => "index"],
        "myAddressesShow" => ["class" => "MyAddress", "method" => "Show"],
        "myRequests" => ["class" => "MyRequest", "method" => "index"],
        "myRequestShow" => ["class" => "MyRequest", "method" => "show"],

        "flow" => ["class" => "Flow", "method" => "input"],
    ],
    "keywords" => [
        "beginning" => "شروع (عضویت در ربات)",
        "account" => "حساب کاربری",
        "aboutUs" => "درباره ما",
        "support" => "پشتیبانی",
        "submitTravel" => "ثبت سفر",
        "submitPackage" => "ثبت بسته",
        "myRequests" => "درخواست‌های من",
        "myAddresses" => "آدرس‌های من",
        "sharePhone" => "اشتراک گذاری شماره",
        "backward" => "بازگشت"

    ],
    "messages" => [
        "guestMenu" => "منوی میهمان",
        "mainMenu" => "منوی اصلی",

        "inputContact" => "شماره خود را با زدن دکمه زیر به اشتراک بگذارید",
        "loginSuccessfully" => "باموفقیت وارد شدبد!",

        "errorInvalidContact" => 'خطای شماره موبایل',
        "errorAnotherContact" => 'خطای شماره متعلق به شما نیست',
        "errorInvalidPhone" => 'خطای شماره تلفن',
        "errorInvalidEmail" => 'خطای ایمیل',
        "errorInvalidPhoto" => 'خطای عکس',
    ]
]));
