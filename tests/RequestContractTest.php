<?php

namespace Kavenegar\Tests;

use Kavenegar\KavenegarApi;
use Kavenegar\Models\SendRequest;
use Kavenegar\Models\SendArrayRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RequestContractTest extends TestCase
{
    private function client(): KavenegarApi
    {
        return new class(' dummy ') extends KavenegarApi {
            public array $request = [];
            protected function execute($url, $data = null, $method = 'POST')
            {
                $this->request = ['url' => $url, 'data' => $data, 'method' => $method];
                return [];
            }
        };
    }

    #[DataProvider('lookupArguments')]
    public function testLookupPreservesV21ArgumentOrder(array $args, array $expected): void
    {
        $api = $this->client();
        $api->verifyLookup(...$args);
        self::assertSame('https://api.kavenegar.com/v1/dummy/verify/lookup.json/', $api->request['url']);
        self::assertSame($expected, array_filter($api->request['data'], static fn($value) => $value !== null));
    }

    public static function lookupArguments(): array
    {
        $base = ['receptor' => '09000000000', 'token' => 'otp'];
        return [
            'template at position five' => [['09000000000', 'otp', null, null, 'sms'], $base + ['template' => 'sms']],
            'type is not inferred from token' => [['09000000000', 'otp', 'call', null, 'template', 'sms'], $base + ['token2' => 'call', 'template' => 'template', 'type' => 'sms']],
            'all tokens' => [['09000000000', 'otp', 'two', 'three', 'template', 'call', 'ten words', 'twenty words', 'tag'], $base + ['token2' => 'two', 'token3' => 'three', 'template' => 'template', 'type' => 'call', 'token10' => 'ten words', 'token20' => 'twenty words', 'tag' => 'tag']],
            'numeric enum preserved' => [['09000000000', 'otp', null, null, 'template', 0], $base + ['template' => 'template', 'type' => 0]],
            'named arguments' => [['receptor' => '09000000000', 'token' => 'otp', 'template' => 'template', 'token20' => 'words'], $base + ['template' => 'template', 'token20' => 'words']],
        ];
    }

    public function testSendDtoPreservesDateAndOptionalFields(): void
    {
        $api = $this->client();
        $request = new SendRequest();
        $request->sender = '1000';
        $request->receptor = ['09000000000', '09000000001'];
        $request->message = 'fixture';
        $request->date = new \DateTimeImmutable('@1700000000');
        $request->localids = ['a', 'b'];
        $request->hide = false;
        $request->tag = 'tag';
        $request->mediaid = 'media';
        $api->send($request);
        self::assertSame('09000000000,09000000001', $api->request['data']['receptor']);
        self::assertSame('a,b', $api->request['data']['localid']);
        self::assertSame(1700000000, $api->request['data']['date']);
        self::assertFalse($api->request['data']['hide']);
        self::assertSame('media', $api->request['data']['mediaid']);
        self::assertSame('tag', $api->request['data']['tag']);
    }

    public function testSendArrayDtoPreservesStringLocalIds(): void
    {
        $api = $this->client();
        $request = new SendArrayRequest();
        $request->senders = ['1000'];
        $request->receptors = ['09000000000', '09000000001'];
        $request->messages = ['one', 'two'];
        $request->localmessageids = 'local-id';
        $request->types = 1;
        $api->sendArray($request);
        self::assertSame(['local-id', 'local-id'], json_decode($api->request['data']['localmessageid'], true));
        self::assertSame([1, 1], json_decode($api->request['data']['type'], true));
        self::assertSame(['one', 'two'], json_decode($api->request['data']['message'], true));
    }

    public function testCallTtsSupportsArraysAndDateTime(): void
    {
        $api = $this->client();
        $api->callMakeTTS(['09000000000', '09000000001'], 'fixture', new \DateTimeImmutable('@1700000000'), ['a', 'b'], 'tag');
        self::assertSame('09000000000,09000000001', $api->request['data']['receptor']);
        self::assertSame('a,b', $api->request['data']['localid']);
        self::assertSame(1700000000, $api->request['data']['date']);
        self::assertSame('tag', $api->request['data']['tag']);
    }

    public function testLegacyPostalMethodsRemainAvailable(): void
    {
        $api = $this->client();
        $api->CountPostalcode('1234567890');
        self::assertStringEndsWith('/sms/countpostalcode.json/', $api->request['url']);
        self::assertSame(['postalcode' => '1234567890'], $api->request['data']);
        $api->SendbyPostalcode('1000', '1234567890', 'fixture', 0, 1, 0, 1, null);
        self::assertStringEndsWith('/sms/sendbypostalcode.json/', $api->request['url']);
        self::assertSame('fixture', $api->request['data']['message']);
    }
}
