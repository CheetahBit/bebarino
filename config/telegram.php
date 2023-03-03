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
            "country", "accountName", "accountNumber",
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
        "backward" => ["class" => "Main", "method" => "menu"],
        "beginning" => ["class" => "Main", "method" => "beginning"],
        "support" => ["class" => "Main", "method" => "support"],
        "aboutUs" => ["class" => "Main", "method" => "aboutUs"],

        "account" => ["class" => "Account", "method" => "index"],
        "identityInfo" => ["class" => "Account", "method" => "show"],
        "bankInfo" => ["class" => "Account", "method" => "show"],
        "contactInfo" => ["class" => "Account", "method" => "show"],

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
        "account" => "👤 حساب کاربری",
        "aboutUs" => "🏢 درباره ما",
        "support" => "🎧 پشتیبانی",
        "submitTravel" => "✈️ ثبت سفر",
        "submitPackage" => "📦 ثبت بسته",
        "myRequests" => "📝 درخواست‌های من",
        "myAddresses" => "📍آدرس‌های من",
        "sharePhone" => "📱 اشتراک گذاری شماره",
        "contactSupport" => "ارتباط با پشتیبان",
        "contactInfo" => "📞 اطلاعات تماس",
        "identityInfo" => "🪪 اطلاعات هویتی",
        "bankInfo" => "🏦 اطلاعات بانکی",
        "backward" => "➡️ بازگشت",
        "notEntered" => "وارد نشده",
        "edit" => "✍️ ویرایش",
    ],
    "messages" => [
        "guestMenu" => "منوی میهمان",
        "mainMenu" => "منوی اصلی",
        "aboutUs" => "متن درباره ما",
        "support" => "متن پشتیبانی",
        "accountInfo" => "حساب کاربری",

        "identityInfo" => "اطلاعات هویتی\n\nنام کاربری : :username\nنام : :firstname\nنام خانوادگی : :lastname\nنصویر پاسپورت : :passport",
        "contactInfo" => "اطلاعات تماس\n\nایمیل : :email\nشماره تماس : :phone\nکشور : :country\nشهر : :city\nنشانی : :address",
        "bankInfo" => "اطلاعات بانکی\n\nکشور : :country\nنام صاحب حساب: :accountName\nشماره جساب : :accountNumber",

        "inputContact" => "شماره خود را با زدن دکمه زیر به اشتراک بگذارید",
        "inputUsername" => "نام کاربری خود را وارد کنید",
        "inputFirstname" => "نام خود را وارد کنید",
        "inputLastname" => "نام خانوادگی خود را وارد کنید",
        "inputPassport" => "تصویر پاسپورت خود را وارد کنید",

        "inputPhone" => "شماره موبایل را وارد کنید",
        "inputEmail" => "ایمیل را وارد کنید",
        "inputCountry" => "کشور را انتخاب یا وارد کنید",
        "inputCity" => "شهر را وارد کنید",
        "inputAddress" => "نشانی را وارد کنید",
        "inputAccountName" => "نام صاحب حساب را وارد کنید",
        "inputAccountNumber" => "شماره حساب را وارد کنید",

        "loginSuccessfully" => "باموفقیت وارد شدبد!",
        "saveSuccessfully" => "باموفقیت ذخیره شد !",
        "cancelEdit" => "ویرایش اطلاعات لعو شد",

        "errorInvalidContact" => 'خطای شماره موبایل',
        "errorAnotherContact" => 'خطای شماره متعلق به شما نیست',
        "errorInvalidPhone" => 'خطای شماره تلفن',
        "errorInvalidEmail" => 'خطای ایمیل',
        "errorInvalidPhoto" => 'خطای نثویر',
    ]
]));
