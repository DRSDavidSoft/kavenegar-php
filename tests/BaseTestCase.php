<?php

namespace Kavenegar\Tests;

use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('KAVENEGAR_RUN_LIVE_TESTS') !== '1') {
            $this->markTestSkipped('Live account operations require explicit opt-in.');
        }
        foreach (['KAVENEGAR_API_KEY', 'KAVENEGAR_RECEPTOR', 'KAVENEGAR_SENDER', 'KAVENEGAR_TEMPLATE'] as $name) {
            if (trim((string) getenv($name)) === '') {
                $this->markTestSkipped('Missing live-test setting: ' . $name);
            }
        }
    }

    public static function getApiKey()
    {
        return getenv('KAVENEGAR_API_KEY') ?: "<put your apikey here>";
    }

    public static function getReceptor()
    {
        return getenv('KAVENEGAR_RECEPTOR') ?: "<put your receptor here>";
    }

    public static function getSender()
    {
        return getenv('KAVENEGAR_SENDER') ?: "<put your sender here>" ;
    }

    public static function getTemplate()
    {
        return getenv('KAVENEGAR_TEMPLATE') ?: "<put your template here>";
    }
}
