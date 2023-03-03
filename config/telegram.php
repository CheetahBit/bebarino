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
        "trip" => [
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

        "submitTrip" => ["class" => "Main", "method" => "submitTrip"],
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
        "submitTrip" => "✈️ ثبت سفر",
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
        "delete" => "🗑️ حذف",
        "createAddress" => "➕ افزودن آدرس",
        "indexAddress" => "📍 نمایش آدرس‌ها",
        "selectAddress" => "📍 انتخاب آدرس",
        "sendFormRequest" => "📨 ارسال درخواست",

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
        
        "myAddresses" => "آدرس‌های من",
        "addressInfo" => "آدرس\n\nکشور : :country\nشهر : :city\nنشانی : :address",

        "submitPackage" => "ثبت بسته \n\nلطفا اطلاعات زیر را وارد نمایید",
        "submitTrip" => "ثبت سفر \n\nلطفا اطلاعات زیر را وارد نمایید",

        "deleted" => "حذف شد",
        "removeKeyboard" => ".",

        "channelPackage" => "بسته جدید\n\nاز مبدا : :fromAddress\nبه مقصد : :toAddress\n\nتوضیحات : :desc",
        "packageSubmitted" => "بسته شما ثبت شد\n\nt.me/:channel/:post",


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
        "inputDesc" => "توضیحات را وارد کنید",
        "inputDate" => "تاریخ سفر را وارد کنید",
        "inputTicket" => "تصویر بلیط را وارد کنید",
        "inputWeight" => "حداکثر وزن را وارد کنید",
        "inputPrice" => "قیمت پیشنهادی را وارد کنید",

        "selectFromAddress" => "آدرس مبدا را انتخاب کنید",
        "selectToAddress" => "آدرس مقصد را انتخاب کنید",

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
