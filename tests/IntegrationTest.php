<?php

namespace Kavenegar\Tests;

use PHPUnit\Framework\TestCase;
use Kavenegar\KavenegarApi;
use Kavenegar\HttpClient;

class IntegrationTest extends TestCase
{
    public function testKavenegarApiWithCustomHttpClient(): void
    {
        $httpClient = new HttpClient('CustomAgent/1.0');
        $api = new KavenegarApi('test-api-key-123456', false, $httpClient);
        
        $this->assertInstanceOf(KavenegarApi::class, $api);
    }

    public function testKavenegarApiWithDefaultHttpClient(): void
    {
        $api = new KavenegarApi('test-api-key-123456');
        
        $this->assertInstanceOf(KavenegarApi::class, $api);
    }

    public function testKavenegarApiWithInsecureFlag(): void
    {
        $api = new KavenegarApi('test-api-key-123456', true);
        
        $this->assertInstanceOf(KavenegarApi::class, $api);
    }
}
