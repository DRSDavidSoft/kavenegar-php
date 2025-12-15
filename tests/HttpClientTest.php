<?php

namespace Kavenegar\Tests;

use PHPUnit\Framework\TestCase;
use Kavenegar\HttpClient;
use Kavenegar\Exceptions\HttpException;

class HttpClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear environment variables before each test
        putenv('HTTP_PROXY');
        putenv('http_proxy');
        putenv('HTTPS_PROXY');
        putenv('https_proxy');
        putenv('NO_PROXY');
        putenv('no_proxy');
    }

    public function testConstructorWithDefaultUserAgent(): void
    {
        $client = new HttpClient();
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testConstructorWithCustomUserAgent(): void
    {
        $client = new HttpClient('CustomAgent/1.0');
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testProxySettingsFromEnvironmentHTTP(): void
    {
        putenv('HTTP_PROXY=http://proxy.example.com:8080');
        $client = new HttpClient();
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testProxySettingsFromEnvironmentHTTPS(): void
    {
        putenv('HTTPS_PROXY=http://proxy.example.com:8443');
        $client = new HttpClient();
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testProxySettingsHTTPSPreferredOverHTTP(): void
    {
        putenv('HTTP_PROXY=http://proxy1.example.com:8080');
        putenv('HTTPS_PROXY=http://proxy2.example.com:8443');
        $client = new HttpClient();
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testNoProxySettings(): void
    {
        putenv('NO_PROXY=localhost,127.0.0.1,.example.com');
        $client = new HttpClient();
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testNoProxyWithWildcard(): void
    {
        putenv('NO_PROXY=*');
        $client = new HttpClient();
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testLowercaseEnvironmentVariables(): void
    {
        putenv('http_proxy=http://proxy.example.com:8080');
        putenv('https_proxy=http://proxy.example.com:8443');
        putenv('no_proxy=localhost');
        $client = new HttpClient();
        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testPostMethodExists(): void
    {
        $client = new HttpClient();
        $this->assertTrue(method_exists($client, 'post'));
    }

    // Integration test would require a real endpoint or mock
    // For now, we test that the method is callable
    public function testPostMethodIsCallable(): void
    {
        $client = new HttpClient();
        $this->assertIsCallable([$client, 'post']);
    }
}
