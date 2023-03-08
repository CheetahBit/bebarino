<?php

return json_decode(json_encode([
    "token" => env("BOT_TOKEN"),
    "channel" => "bebarinoTest",
    "bot" => "follow4u_bot",
    // "admins" => [1613366049],
    "admins" => [1309121632],
    "support" => 1613366049,
    "flows" => [
        "beginning" => [
            "contact"
        ],
        "identity" => [
            "username", "fullname", "passport"
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
            "fromCountry", "fromCity", "fromAddress",
            "toCountry", "toCity", "toAddress",
            "date", "ticket", "weight", "price", "desc"
        ],
        "package" => [
            "fromCountry", "fromCity", "fromAddress",
            "toCountry", "toCity", "toAddress",
            "desc"
        ]
    ],
    "optionals" => ["identity", "contact", "bank"],
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

        "requestTrip" => ["class" => "Package", "method" => "form"],
        "requestPackage" => ["class" => "Trip", "method" => "form"],

        "myAddresses" => ["class" => "MyAddress", "method" => "index"],
        "myAddressesShow" => ["class" => "MyAddress", "method" => "show"],
        "createAddress" => ["class" => "MyAddress", "method" => "create"],

        "myRequests" => ["class" => "MyRequest", "method" => "index"],
        "myRequestShow" => ["class" => "MyRequest", "method" => "show"],

        "flow" => ["class" => "Flow", "method" => "input"],

        "selectPackage" => ["class" => "Package", "method" => "select"],
        "createPackage" => ["class" => "Package", "method" => "create"],

        "selectTrip" => ["class" => "Trip", "method" => "select"],
        "createTrip" => ["class" => "Trip", "method" => "create"],
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
        "selectTrip" => "✈️ انتخاب سفر",
        "createTrip" => "➕ افزودن سفر",
        "indexRequest" => "نمایش درخواست‌ها",
        "sendFormRequest" => "📨 ارسال درخواست",
        "package" => "📦 بسته",
        "trip" => "✈️ سفر",
        "acceptRequest" => "✅ پذیرش درخواست",
        "rejectRequest" => "🚫 رد درخواست",
        "contactTripper" => "✈️ مسافر",
        "tripperDocs" => "اطلاعات مسافر",
        "contactPacker" => "📦 صاحب بسته",
        "packerDocs" => "اطلاعات صاحب بسته",
        "contactAndImageDocs" => "اطلاعات تماس و تصویر مدارک",
        "requestDone" => "✅ واگذار شد",
        "confirm" => "✅ تایید",
        "cancel" => "🚫 انصراف",
        "showInChannel" => "نمایش در کانال",
        "statusOpened" => "باز",
        "statusClosed" => "بسته",

        "closeRequest" => "بستن درخواست",
        "openRequest" => "باز کردن درخواست",

        "statusPendingAdmin" => "در انتظار بررسی ادمین",
        "statusClosedByAdmin" => "بسته شده توسط ادمین",
        "sendRequestToChannel" => "ارسال درخواست به کانال",

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

        "deleted" => "حذف شد",
        "removeKeyboard" => ".",

        "submitPackage" => "ثبت بسته \n\nلطفا اطلاعات زیر را وارد نمایید",
        "submitTrip" => "ثبت سفر \n\nلطفا اطلاعات زیر را وارد نمایید",

        "confirmPackage" => "ثبت بسته\n\nاز مبدا : :fromCountry , :fromCity , :fromAddress\nبه مقصد : :toCountry , :toCity , :toAddress\n\nتوضیحات : :desc\n\nآیا این اطلاعات مورد تایید است؟",
        "confirmTrip" => "ثبت سفر\n\nاز مبدا : :fromCountry , :fromCity , :fromAddress\nبه مقصد : :toCountry , :toCity , :toAddress\n\nتاریخ سفر : :date\nجداکثر وزن : :weight\nقیمت پیشنهادی : :price\nتصویر بلیط : :ticket\n\nتوضیحات : :desc\n\nآیا این اطلاعات مورد تایید است؟",

        "channelPackage" => "📦 بسته جدید \n\nشماره درخواست :code\n\nاز مبدا : :fromCountry , :fromCity\nبه مقصد : :toCountry , :toCity\n\nتوضیحات : :desc\n\n:hasPassport تصویر مدارک شناسایی\n:hasContact اطلاعات تماس\n\nوضعیت درخواست : :status",
        "channelTrip" => "✈️ سفر جدید\n\nشماره درخواست :code\n\nاز مبدا : :fromCountry , :fromCity\nبه مقصد : :toCountry , :toCity\n\nتاریخ سفر : :date\nجداکثر وزن : :weight\nقیمت پیشنهادی : :price\n\nتوضیحات : :desc\n\n:hasTicket تصثویر بلیط\n:hasPassport تصویر مدارک شناسایی\n:hasContact اطلاعات تماس\n\nوضعیت درخواست : :status",

        "packageSubmitted" => "بسته شما ثبت شد\n\nشماره درخواست :code\n\nاز مبدا : :fromCountry , :fromCity , :fromAddress\nبه مقصد : :toCountry , :toCity , :toAddress\n\nتوضیحات : :desc",
        "tripSubmitted" => "سفر شما ثبت شد\n\nشماره درخواست :code\n\nاز مبدا : :fromCountry , :fromCity , :fromAddress\nبه مقصد : :toCountry , :toCity , :toAddress\n\nتاریخ سفر : :date\nجداکثر وزن : :weight\nقیمت پیشنهادی : :price\nتصویر بلیط : :ticket\n\nتوضیحات : :desc",

        "packageInfo" => "بسته\n\nمبدا : :fromCountry , :fromCity\nبه مقصد : :toCountry , :toCity\n\nنوضیحات: :desc\n\nوضعیت درخواست : :status",
        "tripInfo" => "سفر\n\nمبدا : :fromCountry , :fromCity\nبه مقصد : :toCountry , :toCity\n\nتاریخ سفر : :date\nجداکثر وزن : :weight\nقیمت پیشنهادی : :price\nنصویر بلیط : :ticket\n\nنوضیحات: :desc\n\nوضعیت درخواست : :status",

        "requestClosed" => "درخواست بسته شد",
        "requestOpened" => "درخواست باز شد",

        "requestFormSent" => "فرم درخواست برای شما از طریق ربات ارسال شد",
        "requestIsDone" => "این مورد قبلا انجام شده است!",
        "requestIsSelf" => "این درخواست متعلق به شماست",
        "requestIsClosed" => "این درخواست بسته شده است",
        "requestAlready" => "قبلا درخواست ارسال کرده اید",

        "requestTripForm" => "درخواست حمل بسته\n\nشماره درخواست :code\n\nاز مبدا : :fromCountry , :fromCity\nبه مقصد : :toCountry , :toCity\n\nتاریخ سفر : :date\nجداکثر وزن : :weight\nقیمت پیشنهادی : :price\n\nتوضیحات : :desc\n\n:hasTicket تصثویر بلیط\n:hasPassport تصویر مدارک شناسایی\n:hasContact اطلاعات تماس\n\nلطفا به کمک دکمه های زیر بسته خود را انتخاب یا ایجاد کنید",
        "requestTripSent" => "درخواست شما برای مسافر ارسال شد \n\nلطفا منتظر نتیجه درخواست بمانید\n\nشماره درخواست :code\n\\nمبدا : :fromCountry , :fromCity , :fromAddress\nبه مقصد : :toCountry , :toCity , :toAddress\n\nنوضیحات: :desc\n\n:hasPassport تصویر مدارک شناسایی\n:hasContact اطلاعات تماس",
        "requestTrip" => "درخواست بردن بسته \n\nاطلاعات بسته\n\nمبدا : :fromCountry , :fromCity\nبه مقصد : :toCountry , :toCity\n\nنوضیحات: :desc\n\n:hasPassport تصویر مدارک شناسایی\n:hasContact اطلاعات تماس",

        "requestPackageForm" => "درخواست بردن بسته\n\nشماره درخواست :code\n\nاز مبدا : :fromCountry , :fromCity\nبه مقصد : :toCountry , :toCity\n\nتوضیحات : :desc\n\n:hasPassport تصویر مدارک شناسایی\n:hasContact اطلاعات تماس\n\nلطفا به کمک دکمه های زیر سفر خود را انتخاب یا ایجاد کنید",
        "requestPackageSent" => "درخواست شما برای صاحب بسته ارسال شد \n\nلطفا منتظر نتیجه درخواست بمانید\n\nشماره درخواست :code\n\nمبدا : :fromCountry , :fromCity ,:fromAddress\nمقصد : :toCountry , :toCity , :toAddress\n\nتاریخ سفر : :date\nجداکثر وزن : :weight\nقیمت پیشنهادی : :price\nتصویر بلیط : :ticket\n\nنوضیحات: :desc\n\n:hasTicket تصویر بلیط\n:hasPassport تصویر مدارک شناسایی\n:hasContact اطلاعات تماس",
        "requestPackage" => "درخواست حمل بسته\n\nاز مبدا : :fromCountry , :fromCity\nبه مقصد : :toCountry , :toCity\n\nتاریخ سفر : :date\nجداکثر وزن : :weight\nقیمت پیشنهادی : :price\n\nتوضیحات : :desc\n\n:hasTicket تصویر بلیط\n:hasPassport تصویر مدارک شناسایی\n:hasContact اطلاعات تماس",

        "pending" => 'در انتظار تایید',
        "pendingAdmin" => 'در انتظار بررسی ادمین',

        "rejectRequest" => 'درخواست رد شد',
        "acceptRequest" => 'درخواست تایید شد',

        "requestPackageAdmin" => "سفر\n\nشماره درخواست :code\n\nمبدا : :fromCountry , :fromCity\nبه مقصد : :toCountry , :toCity\n\nتاریخ سفر : :date\nجداکثر وزن : :weight\nقیمت پیشنهادی : :price\n\nنوضیحات: :desc\n\n:hasTicket تصثویر بلیط\n:hasPassport تصویر مدارک شناسایی\n:hasContact اطلاعات تماس\n\n\nبسته\n\nشماره درخواست :packageCode\n\nمبدا : :packageFromAddress\n\nمقصد : :packageToAddress\n\nنوضیحات: :packageDesc\n\n:packageHasPassport تصویر مدارک شناسایی\n:packageHasContact اطلاعات تماس",
        "requestTripAdmin" => "بسته\n\nشماره درخواست :packageCode\n\nمبدا : :packageFromAddress\n\nمقصد : :packageToAddress\n\nنوضیحات: :packageDesc\n\n:packageHasPassport تصویر مدارک شناسایی\n:packageHasContact اطلاعات تماس\n\n\nسفر\n\nشماره درخواست :code\n\nمبدا : :fromAddress\n\nمقصد : :toAddress\n\nتاریخ سفر : :date\nجداکثر وزن : :weight\nقیمت پیشنهادی : :price\n\nنوضیحات: :desc\n\n:hasTicket تصویر بلیط\n:hasPassport تصویر مدارک شناسایی\n:hasContact اطلاعات تماس",

        "requestClosedByAdmin" => " درخواست شماره :code توسط ادمین بسته شد",
        "requestDeletedByAdmin" => " درخواست شماره :code توسط ادمین حذف شد",
        "requestClosed" => "درخواست بسته شد",



        "noDocs" => "مدرکی جهت نمایش وجود ندارد",
        "sentDocs" => "مدارک ارسال شد",

        "tripsGroup" => "حمل بار\n\nپروازهای :month\n:trips",


        "inputContact" => "شماره خود را با زدن دکمه زیر به اشتراک بگذارید",
        "inputUsername" => "نام کاربری خود را وارد کنید",
        "inputFullname" => "نام خود را وارد کنید",
        "inputPassport" => "تصویر پاسپورت خود را وارد کنید",

        "inputPhone" => "شماره موبایل را وارد کنید",
        "inputEmail" => "ایمیل را وارد کنید",
        "inputCountry" => "کشور را انتخاب یا وارد کنید",
        "inputCity" => "شهر را وارد کنید",
        "inputAddress" => "نشانی را وارد کنید",
        "inputAccountName" => "نام صاحب حساب را وارد کنید",
        "inputAccountNumber" => "شماره حساب را وارد کنید",

        "inputFromCountry" => "کشور مبدا را انتخاب یا وارد کنید",
        "inputFromCity" => "شهر مبدا را وارد کنید",
        "inputFromAddress" => "نشانی مبدا را وارد کنید",
        "inputOrSelectFromAddress" => "نشانی مبدا را انتخاب یا وارد کنید",

        "inputToCountry" => "کشور مقصد را انتخاب یا وارد کنید",
        "inputToCity" => "شهر مقصد را وارد کنید",
        "inputToAddress" => "نشانی مقصد را وارد کنید",
        "inputOrSelectToAddress" => "نشانی مقصد را انتخاب یا وارد کنید",

        "inputDesc" => "توضیحات را وارد کنید",
        "inputDate" => "تاریخ سفر را وارد کنید \n\n قالب : yyyy/mm/dd",
        "inputTicket" => "تصویر بلیط را وارد کنید",
        "inputWeight" => "حداکثر وزن(کیلوگرم) یا تعداد(عدد) قابل حمل را وارد کنید",
        "inputPrice" => "قیمت پیشنهادی را به همراه نوع ارز وارد کنید\n\nدلار آمریکا ، یورو ، دلار کانادا",

        "loginSuccessfully" => "باموفقیت وارد شدبد!",
        "saveSuccessfully" => "باموفقیت ذخیره شد !",
        "notFound" => "موردی یافت نشد!",
        "notEditable" => "امکان ویرایش وجود ندارد",

        "errorInvalidContact" => 'خطای شماره موبایل',
        "errorAnotherContact" => 'خطای شماره متعلق به شما نیست',
        "errorInvalidPhone" => 'خطای شماره تلفن',
        "errorInvalidEmail" => 'خطای ایمیل',
        "errorInvalidPhoto" => 'خطای تصویر',
        "errorInvalidDate" => 'خطای تاریخ',
        "errorDatePast" => 'تاریخ گذشته',

        "needLogin" => "ابتدا باید وارد ربات شوید!"
    ],
    "dateRegex" => "/^(?:(?:31(\/|-|\.)(?:0?[13578]|1[02]))\1|(?:(?:29|30)(\/|-|\.)(?:0?[13-9]|1[0-2])\2))(?:(?:1[6-9]|[2-9]\d)?\d{2})$|^(?:29(\/|-|\.)0?2\3(?:(?:(?:1[6-9]|[2-9]\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\d|2[0-8])(\/|-|\.)(?:(?:0?[1-9])|(?:1[0-2]))\4(?:(?:1[6-9]|[2-9]\d)?\d{2})$/"
]));
