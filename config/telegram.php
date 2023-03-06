<?php

return json_decode(json_encode([
    "token" => env("BOT_TOKEN"),
    "channel" => "bebarinoTest",
    "admins" => [1645621910],
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
        "myAddressesShow" => ["class" => "MyAddress", "method" => "show"],
        "createAddress" => ["class" => "MyAddress", "method" => "create"],

        "myRequests" => ["class" => "MyRequest", "method" => "index"],
        "myRequestShow" => ["class" => "MyRequest", "method" => "show"],

        "flow" => ["class" => "Flow", "method" => "input"],

        "selectPackage" => ["class" => "Package", "method" => "select"],
        "createPackage" => ["class" => "Package", "method" => "create"],
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
        "desire" => "تمایل ندارم",
        "createAddress" => "➕ افزودن آدرس",
        "indexAddress" => "📍 نمایش آدرس‌ها",
        "selectAddress" => "📍 انتخاب آدرس",
        "selectPackage" => "📦 انتخاب بسته",
        "createPackage" => "➕ افزودن بسته",
        "indexRequest" => "نمایش درخواست‌ها",
        "sendFormRequest" => "📨 ارسال درخواست",
        "package" => "بسته",
        "trip" => "سفر",
        "acceptRequest" => "✅ پذیرش درخواست",
        "rejectRequest" => "🚫 رد درخواست",
        "contactTripper" => "✈️ مسافر",
        "contactPacker" => "📦 صاحب بسته",
        "imageDocs" => "تصویر مدارک",
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

        "myRequests" => "درخواست‌های من",

        "submitPackage" => "ثبت بسته \n\nلطفا اطلاعات زیر را وارد نمایید",
        "submitTrip" => "ثبت سفر \n\nلطفا اطلاعات زیر را وارد نمایید",

        "deleted" => "حذف شد",
        "removeKeyboard" => ".",

        "channelPackage" => "بسته جدید\n\nاز مبدا : :fromAddress\nبه مقصد : :toAddress\n\nتوضیحات : :desc",
        "channelTrip" => "سفر جدید\n\nاز مبدا : :fromAddress\nبه مقصد : :toAddress\n\nتاریخ سفر : :date\nجداکثر وزن : :weight\nقیمت پیشنهادی : :price\n\n:hasTicket تصثویر بلیط\n:hasPassport تصویر مدارک شناسایی\n:hasContact اظلاعات تماس\n\nتوضیحات : :desc",

        "packageSubmitted" => "بسته شما ثبت شد\n\nt.me/:channel/:post",
        "tripSubmitted" => "سفر شما ثبت شد\n\nt.me/:channel/:post",

        "packageInfo" => "بسته\n\nمبدا : :fromAddress\n\nمقصد : :toAddress\n\nنوضیحات: :desc",
        "tripInfo" => "سفر\n\nمبدا : :fromAddress\n\nمقصد : :toAddress\n\nتاریخ سفر : :date\nجداکثر وزن : :weight\nقیمت پیشنهادی : :price\nنصویر بلیط : :ticket\n\nنوضیحات: :desc",

        "requestFormSent" => "فرم درخواست برای شما از طریق ربات ارسال شد",
        "requestIsDone" => "این مورد قبلا انجام شده است!",
        "requestSelf" => "این درخواست متعلق به شماست",

        "requestTripForm" => "درخواست حمل بسته\n\nلطفا به کمک دکمه های زیر بسته خود را انتخاب یا ایجاد کنید",
        "requestTripSent" => "درخواست شما برای مسافر ارسال شد \n\nلطفا منتظر نتیجه درخواست بمانید\n\nمبدا : :fromAddress\n\nمقصد : :toAddress\n\nنوضیحات: :desc",
        "requestTrip" => "درخواست بردن بسته \n\nاطلاعات بسته\n\nمبدا : :fromAddress\n\nمقصد : :toAddress\n\nنوضیحات: :desc",

        "requestPackageForm" => "درخواست بردن بسته\n\nلطفا به کمک دکمه های زیر سفر خود را انتخاب یا ایجاد کنید",
        "requestPackageSent" => "درخواست بردن بسته\n\nلطفا به کمک دکمه های زیر سفر خود را انتخاب یا ایجاد کنید",
        "requestPackage" => "درخواست بردن بسته\n\nلطفا به کمک دکمه های زیر سفر خود را انتخاب یا ایجاد کنید",

        "pending" => 'در انتظار تایید',
        "pendingAdmin" => 'در انتظار بررسی ادمین',

        "rejectRequest" => 'درخواست رد شد',
        "acceptRequest" => 'درخواست تایید شد',

        "requestPackageAdmin" => "سفر\n\nمبدا : :fromAddress\n\nمقصد : :toAddress\n\nتاریخ سفر : :date\nجداکثر وزن : :weight\n\nقیمت پیشنهادی : :price\n:hasTicket تصثویر بلیط\n:hasPassport تصویر مدارک شناسایی\n:hasContact اطلاعات تماس\n\nنوضیحات: :tripDesc\n\n\nبسته\n\nمبدا : :fromAddress\n\nمقصد : :toAddress\n\nنوضیحات: :packageDsc",
        "requestTripAdmin" => "بسته\n\nمبدا : :fromAddress\n\nمقصد : :toAddress\n\nنوضیحات: :packageDesc\n\n\nسفر\n\nمبدا : :fromAddress\n\nمقصد : :toAddress\n\nتاریخ سفر : :date\nجداکثر وزن : :weight\nقیمت پیشنهادی : :price\n\n:hasTicket تصثویر بلیط\n:hasPassport تصویر مدارک شناسایی\n:hasContact اظلاعات تماس\n\nنوضیحات: :tripDesc",



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
