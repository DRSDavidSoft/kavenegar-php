<div align="center">

<br/>

# 📱 Kavenegar PHP SDK

**A modern, lightweight PHP client for SMS, OTP & Voice — request/result models with PHP 8.1+ support.**

<br/>

[![Packagist Version](https://img.shields.io/packagist/v/kavenegar/php?style=for-the-badge&color=8892BF&logo=composer&label=Packagist)](https://packagist.org/packages/kavenegar/php)
[![Downloads](https://img.shields.io/packagist/dt/kavenegar/php?style=for-the-badge&color=00b4d8&logo=composer&label=Downloads)](https://packagist.org/packages/kavenegar/php)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.1-777BB4?style=for-the-badge&logo=php)](https://php.net)

<br/>

[📦 Install](#-installation) · [🚀 Quick Start](#-quick-start) · [📝 DTO Requests](#-dto-request-objects) · [📖 Docs](http://kavenegar.com/rest.html) · [🇮🇷 فارسی](#-راهنما)

<br/>

</div>

---

## ✨ Features

* 📦 **Strict DTO Mapping:** Method calls explicitly return fully structured model objects (e.g., `SendResult`, `StatusResult`) instead of raw, untyped associative arrays.
* 🔧 **Flexible Signatures:** Supports both traditional parameter lists and C#-style request DTO objects.
* ⚡ **Zero External Dependencies:** Built on native PHP CURL for high performance and compatibility.
* 🌐 **PHP 8.1+:** Requires the cURL and JSON extensions. CI checks PHP 8.1–8.5.
* 🔒 **Verified HTTPS:** Certificate and hostname verification are enabled by default.

---

## 📦 Installation

Install the package via **Composer**:

```bash
composer require kavenegar/php
```

---

## Compatibility and migration

This `3.0.0-dev` branch requires PHP **8.1+**, replacing the old PHP 5.4 minimum.
The Composer install command selects the published release; it does not install this unmerged branch.

* The v2.1 endpoint names, request DTOs, optional parameters, `DateTimeInterface`
  dates and result models are retained. SMS/lookup calls return an array of
  `SendResult` objects: iterate `$result` directly, not `$result->entries`.
  You do not need to call `json_decode()`. Other endpoints retain their v2.1
  model, list or scalar return values.
* `verifyLookup` keeps the **v2.1.0** positional order below. Earlier drafts of
  PR #23 placed `token10`/`token20` before `template`; update such calls.
  The ambiguous argument-count overloads in v2.0.0 are not supported.
* Empty, whitespace-only or null API keys now throw
  `NotProperlyConfiguredException`, instead of terminating the PHP process.
* HTTPS certificate and hostname verification default to **on**. Configure a
  trusted CA bundle through PHP's `curl.cainfo` if your installation needs it.
  `setSslVerify(false)` (or the fourth constructor argument) is an explicit
  local-test-only escape hatch. `$insecure = true` selects unencrypted HTTP;
  it is separate from certificate verification and should not be used in production.
* The protected transport properties are declared and typed. Subclasses that
  redeclare them must use compatible types. Public endpoint arguments retain
  upstream's flexible DTO/scalar forms.
* The legacy `CountPostalcode` and `SendbyPostalcode` entry points are retained.

```php
$result = $api->verifyLookup(
    $receptor,
    $token,
    $token2 = null,
    $token3 = null,
    $template = null,
    $type = null,
    $token10 = null,
    $token20 = null,
    $tag = null
);

// Typical OTP call with the template in position 5:
$result = $api->verifyLookup('09123456789', '123456', null, null, 'my-template', 'sms');
```

## 🚀 Quick Start

Initialize the client with your API key:

```php
require __DIR__ . '/vendor/autoload.php';

use Kavenegar\KavenegarApi;
use Kavenegar\Exceptions\ApiException;
use Kavenegar\Exceptions\HttpException;

$api = new KavenegarApi("YOUR_API_KEY");

try {
    // Send a simple SMS
    $result = $api->send("10004346", "09123456789", "Hello from Kavenegar PHP SDK!");

    foreach ($result as $sms) {
        echo "✅ Sent! Message ID: " . $sms->messageid . "\n";
    }
} catch (ApiException $e) {
    echo "❌ API Error: " . $e->errorMessage() . "\n";
} catch (HttpException $e) {
    echo "❌ Network Error: " . $e->errorMessage() . "\n";
}
```

---

## 📝 DTO Request Objects

For more structured request configuration, you can pass request DTOs directly to the methods:

### Sending SMS with `SendRequest`

```php
use Kavenegar\Models\SendRequest;

$req = new SendRequest();
$req->sender = "10004346";
$req->receptor = "09123456789";
$req->message = "OTP Code: 123456";
$req->policy = "policy_value"; // Optional policy

$result = $api->send($req);
```

### Adding Templates with `TemplateDto` & Enums

```php
use Kavenegar\Models\TemplateDto;
use Kavenegar\Enums\VerificationType;
use Kavenegar\Enums\VerificationUsageType;

$dto = new TemplateDto();
$dto->name = "otp-template-name";
$dto->textmessage = "Your verification code: %token%";
$dto->sourcetype = VerificationUsageType::MobileApplication;
$dto->sendmethod = VerificationType::Message;

$newTemplate = $api->addTemplate($dto);
```

---

## Tests

```bash
composer install
composer validate --strict
composer lint
composer test
```

The default Unit suite uses local fixtures and dummy credentials. It tests request
encoding, result mapping, lookup argument order, HTTP/JSON errors and TLS defaults
without sending SMS or changing a Kavenegar account. Syntax checks run on Windows
and Unix and fail the build on error.

The inherited live examples are a separate `Live` suite (`composer test:live`).
They are skipped unless `KAVENEGAR_RUN_LIVE_TESTS=1` and `KAVENEGAR_API_KEY`,
`KAVENEGAR_RECEPTOR`, `KAVENEGAR_SENDER`, `KAVENEGAR_TEMPLATE` are configured.
Review their placeholder IDs and account-specific fixtures before opting in:
they send messages and modify contacts, templates, media and sub-clients, and can
consume credit. Offline CI is not a claim that these live workflows passed.

## 🤝 Contributing

Contributions, bug reports, and pull requests are welcome!

| | |
|---|---|
| 🐛 **Bug?** | Open an [issue](../../issues) |
| 💡 **Idea?** | Submit a [pull request](../../pulls) |
| 📧 **Help?** | [support@kavenegar.com](mailto:support@kavenegar.com?Subject=SDK) |

---

<br/>

<div dir="rtl">

## 🇮🇷 راهنما

### معرفی سرویس کاوه نگار

کاوه نگار یک وب سرویس ارسال و دریافت پیامک و تماس صوتی است که به راحتی میتوانید از آن استفاده نمایید.

### ساخت حساب کاربری

اگر در وب سرویس کاوه نگار عضو نیستید میتوانید از [لینک عضویت](http://panel.kavenegar.com/client/membership/register) ثبت نام و اکانت آزمایشی برای تست API دریافت نمایید.

### مستندات

برای مشاهده اطلاعات کامل مستندات [وب سرویس پیامک](http://kavenegar.com/وب-سرویس-پیامک.html) به صفحه [مستندات وب سرویس](http://kavenegar.com/rest.html) مراجعه نمایید.

</div>
