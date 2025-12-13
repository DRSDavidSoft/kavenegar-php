# Kavenegar PHP SDK

[![Latest Stable Version](https://poser.pugx.org/kavenegar/php/v/stable.svg)](https://packagist.org/packages/kavenegar/php)
[![Total Downloads](https://poser.pugx.org/kavenegar/php/downloads.svg)](https://packagist.org/packages/kavenegar/php)

> **Language**: English | [فارسی](README.fa.md)

A professional PHP wrapper for the Kavenegar API, enabling seamless integration of SMS and voice call services into your PHP applications.

## About Kavenegar

Kavenegar is a comprehensive web service platform for sending and receiving SMS messages and managing voice calls. This SDK provides a simple and efficient way to interact with the Kavenegar RESTful API.

For complete API documentation, please visit the [Kavenegar REST API Documentation](http://kavenegar.com/rest.html).

## Requirements

- PHP 8.1 or higher
- cURL extension enabled

## Installation

Install the package via Composer:

```bash
composer require kavenegar/php
```

Alternatively, add the following to your `composer.json` file:

```json
{
    "require": {
        "kavenegar/php": "*"
    }
}
```

Then run:

```bash
composer update
```

## Getting Started

### 1. Create an Account

If you don't have a Kavenegar account yet, you can register for free at [Kavenegar Registration](https://panel.kavenegar.com/Client/Membership/Register).

### 2. Obtain Your API Key

After registration, retrieve your API key from the [Account Settings](http://panel.kavenegar.com/Client/setting/index) section of your Kavenegar panel.

## Usage

### Basic Example: Sending SMS

```php
<?php

require __DIR__ . '/vendor/autoload.php';

try {
    $api = new \Kavenegar\KavenegarApi("YOUR_API_KEY");
    
    $sender = "10004346";
    $message = "Your message content";
    $receptor = ["09123456789", "09367891011"];
    
    $result = $api->Send($sender, $receptor, $message);
    
    if ($result) {
        foreach ($result as $r) {
            echo "Message ID: " . $r->messageid . "\n";
            echo "Message: " . $r->message . "\n";
            echo "Status: " . $r->status . "\n";
            echo "Status Text: " . $r->statustext . "\n";
            echo "Sender: " . $r->sender . "\n";
            echo "Receptor: " . $r->receptor . "\n";
            echo "Date: " . $r->date . "\n";
            echo "Cost: " . $r->cost . "\n";
        }
    }
} catch (\Kavenegar\Exceptions\ApiException $e) {
    // Handle API exceptions (non-200 responses)
    echo "API Error: " . $e->errorMessage();
} catch (\Kavenegar\Exceptions\HttpException $e) {
    // Handle HTTP connection errors
    echo "HTTP Error: " . $e->errorMessage();
}
```

### Example Response

```json
{
    "return": {
        "status": 200,
        "message": "Approved"
    },
    "entries": [
        {
            "messageid": 8792343,
            "message": "Your message content",
            "status": 1,
            "statustext": "Queued for sending",
            "sender": "10004346",
            "receptor": "09123456789",
            "date": 1356619709,
            "cost": 120
        },
        {
            "messageid": 8792344,
            "message": "Your message content",
            "status": 1,
            "statustext": "Queued for sending",
            "sender": "10004346",
            "receptor": "09367891011",
            "date": 1356619709,
            "cost": 120
        }
    ]
}
```

## Available Methods

The SDK provides the following methods:

- **Send**: Send SMS to one or more recipients
- **SendArray**: Send multiple SMS messages with different parameters
- **Status**: Check the delivery status of sent messages
- **StatusLocalMessageId**: Check status using local message ID
- **Select**: Retrieve message details
- **SelectOutbox**: Get messages from outbox within a date range
- **LatestOutbox**: Retrieve the latest outbox messages
- **CountOutbox**: Count outbox messages
- **Cancel**: Cancel scheduled messages
- **Receive**: Receive incoming messages
- **CountInbox**: Count inbox messages
- **AccountInfo**: Get account information
- **AccountConfig**: Configure account settings
- **VerifyLookup**: Send verification codes using templates
- **CallMakeTTS**: Make text-to-speech voice calls

## Error Handling

The SDK provides two main exception types:

- **ApiException**: Thrown when the API returns a non-200 status code
- **HttpException**: Thrown when there are connection or HTTP-level errors
- **NotProperlyConfiguredException**: Thrown when the SDK is not properly configured

Always wrap your API calls in try-catch blocks to handle exceptions gracefully.

## Contributing

We welcome contributions! If you encounter any bugs, have suggestions for improvements, or would like to add new features, please:

- Open an issue on GitHub
- Submit a pull request
- Contact us at [support@kavenegar.com](mailto:support@kavenegar.com)

## License

This project is licensed under the MIT License.

## Links

- [Kavenegar Website](http://kavenegar.com)
- [API Documentation](http://kavenegar.com/rest.html)
- [Support](mailto:support@kavenegar.com)

---

![Kavenegar](http://kavenegar.com/public/images/logo.png)

**Kavenegar** - Professional SMS and Voice Services
