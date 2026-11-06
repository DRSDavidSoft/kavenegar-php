<?php

namespace Kavenegar\Tests;

use PHPUnit\Framework\TestCase;
use Kavenegar\KavenegarApi;
use Kavenegar\Exceptions\NotProperlyConfiguredException;

class KavenegarApiTest extends TestCase
{
    public function testConstructorWithValidApiKey(): void
    {
        $api = new KavenegarApi("test-api-key-123456");
        $this->assertInstanceOf(KavenegarApi::class, $api);
    }

    public function testConstructorWithEmptyApiKeyThrowsException(): void
    {
        $this->expectException(NotProperlyConfiguredException::class);
        $this->expectExceptionMessage('apiKey is empty');
        new KavenegarApi("");
    }

    public function testConstructorWithWhitespaceApiKeyThrowsException(): void
    {
        $this->expectException(NotProperlyConfiguredException::class);
        $this->expectExceptionMessage('apiKey is empty');
        new KavenegarApi("   ");
    }

    public function testConstructorAcceptsInsecureParameter(): void
    {
        $api = new KavenegarApi("test-api-key-123456", true);
        $this->assertInstanceOf(KavenegarApi::class, $api);
    }

    public function testConstructorTrimsApiKey(): void
    {
        $api = new KavenegarApi("  test-api-key-123456  ");
        $this->assertInstanceOf(KavenegarApi::class, $api);
    }

    public function testNullApiKeyThrowsConfigurationException(): void
    {
        $this->expectException(NotProperlyConfiguredException::class);
        new KavenegarApi(null);
    }

    public function testTlsVerificationDefaultsOnAndCanBeExplicitlyConfigured(): void
    {
        $api = new KavenegarApi('dummy');
        self::assertTrue($api->getSslVerify());
        self::assertSame($api, $api->setSslVerify(false));
        self::assertFalse($api->getSslVerify());
        self::assertTrue($api->setSslVerify(true)->getSslVerify());
        self::assertFalse((new KavenegarApi('dummy', false, null, false))->getSslVerify());
    }
}
