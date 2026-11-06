<?php

namespace Kavenegar\Tests;

use Kavenegar\KavenegarApi;
use Kavenegar\Exceptions\ApiException;
use Kavenegar\Exceptions\HttpException;
use Kavenegar\Models\AccountInfoResult;
use Kavenegar\Models\SendResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TransportTest extends TestCase
{
    private static $server;
    private static array $pipes = [];
    private static string $url;

    public static function setUpBeforeClass(): void
    {
        self::$server = proc_open([PHP_BINARY, __DIR__ . '/Fixtures/http-server.php'],
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], self::$pipes);
        self::assertIsResource(self::$server);
        stream_set_timeout(self::$pipes[1], 10);
        $address = trim((string) fgets(self::$pipes[1]));
        self::assertMatchesRegularExpression('/^127\.0\.0\.1:\d+$/', $address);
        self::$url = 'http://' . $address;
    }

    public static function tearDownAfterClass(): void
    {
        proc_terminate(self::$server);
        foreach (self::$pipes as $pipe) {
            fclose($pipe);
        }
        proc_close(self::$server);
    }

    private function client(): KavenegarApi
    {
        return new class('dummy', false, self::$url) extends KavenegarApi {
            public function request($url, $data = null, $method = 'POST')
            {
                return $this->execute($url, $data, $method);
            }
            public function handle(): ?\CurlHandle
            {
                return $this->curlHandle;
            }
        };
    }

    public function testReusedHandleResetsPostGetDeleteAndMultipartState(): void
    {
        $api = $this->client();
        $post = $api->request(self::$url . '/echo', ['message' => 'سلام & +', 'unused' => null]);
        self::assertSame('POST', $post->method);
        self::assertSame('سلام & +', $post->form->message);
        self::assertObjectNotHasProperty('unused', $post->form);
        self::assertSame('application/x-www-form-urlencoded; charset=utf-8', $post->headers->{'content-type'});
        $handle = $api->handle();

        foreach (['GET', 'DELETE'] as $method) {
            $result = $api->request(self::$url . '/echo?existing=1', ['id' => 'a+b'], $method);
            self::assertSame($method, $result->method);
            self::assertSame('', $result->body);
            self::assertSame('1', $result->query->existing);
            self::assertSame('a+b', $result->query->id);
            self::assertSame($handle, $api->handle());
        }

        $upload = $api->request(self::$url . '/echo', ['File' => new \CURLStringFile('fixture-content', 'test.txt', 'text/plain')]);
        self::assertSame('POST', $upload->method);
        self::assertStringStartsWith('multipart/form-data; boundary=', $upload->headers->{'content-type'});
        self::assertStringContainsString('fixture-content', $upload->body);
        self::assertStringContainsString('filename="test.txt"', $upload->body);

        $post = $api->request(self::$url . '/echo', ['message' => 'after upload']);
        self::assertSame('after upload', $post->form->message);
        self::assertSame('application/x-www-form-urlencoded; charset=utf-8', $post->headers->{'content-type'});
        $api->close();
        self::assertNull($api->handle());
        $api->request(self::$url . '/echo');
        self::assertNotSame($handle, $api->handle());
    }

    public function testPublicMethodsReturnModelsWithoutJsonDecoding(): void
    {
        $api = $this->client();
        $results = $api->send('1000', ['09000000000'], 'fixture message');
        self::assertIsArray($results);
        self::assertInstanceOf(SendResult::class, $results[0]);
        self::assertSame(42, $results[0]->messageid);
        self::assertSame('fixture message', $results[0]->message);
        self::assertInstanceOf(SendResult::class, $api->verifyLookup('09000000000', '123', null, null, 'template')[0]);
        $account = $api->accountInfo();
        self::assertInstanceOf(AccountInfoResult::class, $account);
        self::assertSame(123, $account->remaincredit);
    }

    #[DataProvider('invalidResponses')]
    public function testInvalidResponsesThrowHttpException(string $path, string $message, int $code): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($code);
        $this->client()->request(self::$url . $path);
    }

    public static function invalidResponses(): array
    {
        return [
            ['/malformed', 'Invalid JSON response', 200],
            ['/null', 'Invalid API response envelope', 200],
            ['/scalar', 'Invalid API response envelope', 200],
            ['/missing-return', 'Invalid API response envelope', 200],
            ['/bad-return', 'Invalid API response envelope', 200],
            ['/bad-status', 'Invalid API response envelope', 200],
            ['/missing-message', 'Invalid API response envelope', 200],
            ['/missing-entries', 'Missing entries', 200],
            ['/http-error', 'Unexpected HTTP status', 503],
        ];
    }

    public function testValidApiErrorPreservesStatusAndMessage(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid API key');
        $this->expectExceptionCode(401);
        $this->client()->request(self::$url . '/api-error');
    }

    public function testNullEntriesAreRetained(): void
    {
        self::assertNull($this->client()->request(self::$url . '/null-entries'));
    }
}
