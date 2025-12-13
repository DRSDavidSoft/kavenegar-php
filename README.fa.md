<div dir='rtl'>

# کتابخانه PHP کاوه‌نگار

[![آخرین نسخه پایدار](https://poser.pugx.org/kavenegar/php/v/stable.svg)](https://packagist.org/packages/kavenegar/php)
[![تعداد دانلودها](https://poser.pugx.org/kavenegar/php/downloads.svg)](https://packagist.org/packages/kavenegar/php)

> **زبان**: [English](README.md) | فارسی

کتابخانه حرفه‌ای PHP برای استفاده از API کاوه‌نگار که امکان یکپارچه‌سازی آسان سرویس‌های ارسال پیامک و تماس صوتی را در برنامه‌های PHP فراهم می‌کند.

## معرفی کاوه‌نگار

کاوه‌نگار یک پلتفرم جامع وب سرویس برای ارسال و دریافت پیامک و مدیریت تماس‌های صوتی است. این SDK روشی ساده و کارآمد برای تعامل با API RESTful کاوه‌نگار فراهم می‌کند.

برای مشاهده مستندات کامل API، لطفاً به [مستندات REST API کاوه‌نگار](http://kavenegar.com/rest.html) مراجعه نمایید.

## پیش‌نیازها

- PHP نسخه 8.1 یا بالاتر
- افزونه cURL فعال

## نصب

نصب کتابخانه از طریق Composer:

```bash
composer require kavenegar/php
```

یا می‌توانید موارد زیر را به فایل `composer.json` خود اضافه کنید:

```json
{
    "require": {
        "kavenegar/php": "*"
    }
}
```

سپس دستور زیر را اجرا کنید:

```bash
composer update
```

## شروع به کار

### 1. ایجاد حساب کاربری

اگر هنوز حساب کاربری کاوه‌نگار ندارید، می‌توانید به‌صورت رایگان از طریق [صفحه ثبت‌نام کاوه‌نگار](https://panel.kavenegar.com/Client/Membership/Register) ثبت‌نام کنید.

### 2. دریافت کلید API

پس از ثبت‌نام، کلید API خود را از بخش [تنظیمات حساب کاربری](http://panel.kavenegar.com/Client/setting/index) پنل کاوه‌نگار دریافت نمایید.

## نحوه استفاده

### مثال اولیه: ارسال پیامک

```php
<?php

require __DIR__ . '/vendor/autoload.php';

try {
    $api = new \Kavenegar\KavenegarApi("YOUR_API_KEY");
    
    $sender = "10004346";
    $message = "خدمات پیام کوتاه کاوه‌نگار";
    $receptor = ["09123456789", "09367891011"];
    
    $result = $api->Send($sender, $receptor, $message);
    
    if ($result) {
        foreach ($result as $r) {
            echo "شناسه پیام: " . $r->messageid . "\n";
            echo "متن پیام: " . $r->message . "\n";
            echo "وضعیت: " . $r->status . "\n";
            echo "شرح وضعیت: " . $r->statustext . "\n";
            echo "فرستنده: " . $r->sender . "\n";
            echo "گیرنده: " . $r->receptor . "\n";
            echo "تاریخ: " . $r->date . "\n";
            echo "هزینه: " . $r->cost . "\n";
        }
    }
} catch (\Kavenegar\Exceptions\ApiException $e) {
    // مدیریت خطاهای API (پاسخ‌های غیر 200)
    echo "خطای API: " . $e->errorMessage();
} catch (\Kavenegar\Exceptions\HttpException $e) {
    // مدیریت خطاهای ارتباط HTTP
    echo "خطای HTTP: " . $e->errorMessage();
}
```

### نمونه پاسخ

```json
{
    "return": {
        "status": 200,
        "message": "تایید شد"
    },
    "entries": [
        {
            "messageid": 8792343,
            "message": "خدمات پیام کوتاه کاوه‌نگار",
            "status": 1,
            "statustext": "در صف ارسال",
            "sender": "10004346",
            "receptor": "09123456789",
            "date": 1356619709,
            "cost": 120
        },
        {
            "messageid": 8792344,
            "message": "خدمات پیام کوتاه کاوه‌نگار",
            "status": 1,
            "statustext": "در صف ارسال",
            "sender": "10004346",
            "receptor": "09367891011",
            "date": 1356619709,
            "cost": 120
        }
    ]
}
```

## متدهای موجود

SDK متدهای زیر را فراهم می‌کند:

- **Send**: ارسال پیامک به یک یا چند گیرنده
- **SendArray**: ارسال چندین پیامک با پارامترهای مختلف
- **Status**: بررسی وضعیت تحویل پیام‌های ارسال‌شده
- **StatusLocalMessageId**: بررسی وضعیت با استفاده از شناسه محلی پیام
- **Select**: دریافت جزئیات پیام
- **SelectOutbox**: دریافت پیام‌های صندوق خروجی در یک بازه زمانی
- **LatestOutbox**: دریافت آخرین پیام‌های صندوق خروجی
- **CountOutbox**: شمارش پیام‌های صندوق خروجی
- **Cancel**: لغو پیام‌های زمان‌بندی‌شده
- **Receive**: دریافت پیام‌های دریافتی
- **CountInbox**: شمارش پیام‌های صندوق ورودی
- **CountPostalcode**: شمارش مشترکین بر اساس کد پستی
- **SendbyPostalcode**: ارسال پیامک به گیرندگان فیلتر شده بر اساس کد پستی
- **AccountInfo**: دریافت اطلاعات حساب کاربری
- **AccountConfig**: پیکربندی تنظیمات حساب کاربری
- **VerifyLookup**: ارسال کدهای تایید با استفاده از قالب‌ها
- **CallMakeTTS**: برقراری تماس صوتی متن به گفتار

## مدیریت خطاها

SDK سه نوع استثنای اصلی را ارائه می‌دهد:

- **ApiException**: هنگامی که API کد وضعیت غیر از 200 برمی‌گرداند
- **HttpException**: هنگامی که خطای ارتباطی یا خطای سطح HTTP رخ می‌دهد
- **NotProperlyConfiguredException**: هنگامی که SDK به‌درستی پیکربندی نشده است (مانند نبود کلید API یا غیرفعال بودن افزونه cURL)

همیشه فراخوانی‌های API خود را در بلوک‌های try-catch قرار دهید تا خطاها را به‌درستی مدیریت کنید.

## مشارکت در پروژه

ما از مشارکت‌ها استقبال می‌کنیم! اگر با باگی مواجه شدید، پیشنهادی برای بهبود دارید، یا می‌خواهید ویژگی جدیدی اضافه کنید، لطفاً:

- یک Issue در GitHub ایجاد کنید
- یک Pull Request ارسال کنید
- با ما از طریق [support@kavenegar.com](mailto:support@kavenegar.com) تماس بگیرید

## مجوز

این پروژه تحت مجوز MIT منتشر شده است.

## لینک‌های مفید

- [وب‌سایت کاوه‌نگار](http://kavenegar.com)
- [مستندات API](http://kavenegar.com/rest.html)
- [صفحه SDK](http://kavenegar.com/sdk.html)
- [پشتیبانی](mailto:support@kavenegar.com)

---

![کاوه‌نگار](http://kavenegar.com/public/images/logo.png)

**کاوه‌نگار** - سرویس حرفه‌ای پیامک و تماس صوتی

</div>
