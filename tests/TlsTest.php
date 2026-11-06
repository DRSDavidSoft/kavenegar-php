<?php

namespace Kavenegar\Tests;

use Kavenegar\KavenegarApi;
use Kavenegar\Exceptions\HttpException;
use PHPUnit\Framework\TestCase;

class TlsTest extends TestCase
{
    private static $server;
    private static array $pipes = [];
    private static string $certificate;
    private static string $url;

    public static function setUpBeforeClass(): void
    {
        $options = ['config' => __DIR__ . '/Fixtures/openssl.cnf', 'private_key_bits' => 2048, 'digest_alg' => 'sha256'];
        $key = openssl_pkey_new($options);
        self::assertNotFalse($key);
        $csr = openssl_csr_new(['commonName' => 'localhost'], $key, $options);
        self::assertNotFalse($csr);
        $cert = openssl_csr_sign($csr, null, $key, 1, $options);
        self::assertNotFalse($cert);
        self::assertTrue(openssl_x509_export($cert, $certPem));
        self::assertTrue(openssl_pkey_export($key, $keyPem, null, $options));
        self::$certificate = tempnam(sys_get_temp_dir(), 'kavenegar-tls-');
        file_put_contents(self::$certificate, $certPem . $keyPem);
        self::$server = proc_open([PHP_BINARY, __DIR__ . '/Fixtures/http-server.php', self::$certificate],
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], self::$pipes);
        self::assertIsResource(self::$server);
        stream_set_timeout(self::$pipes[1], 10);
        $address = trim((string) fgets(self::$pipes[1]));
        self::assertMatchesRegularExpression('/^127\.0\.0\.1:\d+$/', $address);
        self::$url = 'https://' . $address;
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            foreach (self::$pipes as $pipe) {
                fclose($pipe);
            }
            proc_close(self::$server);
        }
        if (isset(self::$certificate)) {
            unlink(self::$certificate);
        }
    }

    public function testDefaultRejectsUntrustedCertificateAndClearsFailedHandle(): void
    {
        $api = new class('dummy', false, self::$url) extends KavenegarApi {
            public function hasHandle(): bool
            {
                return $this->curlHandle !== null;
            }
        };
        try {
            $api->accountInfo();
            self::fail('An untrusted TLS certificate must not be accepted by default.');
        } catch (HttpException $e) {
            self::assertSame(CURLE_SSL_CACERT, $e->getCode());
            self::assertFalse($api->hasHandle());
        }
        // The explicit test-only opt-out still works after a failed handshake.
        self::assertSame(123, $api->setSslVerify(false)->accountInfo()->remaincredit);
        $api->close();
        self::assertFalse($api->hasHandle());
    }

    public function testConstructorOptOutIsExplicit(): void
    {
        $api = new KavenegarApi('dummy', false, self::$url, false);
        self::assertSame(123, $api->accountInfo()->remaincredit);
    }
}
