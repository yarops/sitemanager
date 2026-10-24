<?php

namespace common\tests\unit\cloudflare;

use common\components\cloudflare\BatchTimeout;
use common\components\cloudflare\DnsRecords;
use common\components\cloudflare\RequestTimeout;
use PHPUnit\Framework\TestCase;

class FakeDnsRecords extends DnsRecords
{
    /** @var array queued responses: [code, headers, body] or \Throwable */
    public array $responses = [];
    public array $calls = [];
    public array $sleeps = [];

    protected function request(string $token, string $method, string $path, ?array $body): array
    {
        $this->calls[] = [$token, $method, $path, $body];
        $response = array_shift($this->responses);
        if ($response instanceof \Throwable) {
            throw $response;
        }
        if (!is_array($response)) {
            throw new \RuntimeException('Unexpected request');
        }
        return $response;
    }

    protected function sleep(int $seconds): void
    {
        $this->sleeps[] = $seconds;
    }
}

class DnsRecordsTest extends TestCase
{
    private function fixture(string $name): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/dnssync/fixtures/' . $name), true);
    }

    private function client(array $labels = ['a']): FakeDnsRecords
    {
        $accounts = [];
        foreach ($labels as $label) {
            $accounts[$label] = ['id' => "id-$label", 'apikey' => 'monitor', 'dnsApiKey' => "token-$label"];
        }
        // An account without a sync token is ignored.
        $accounts['readonly'] = ['id' => 'id-readonly', 'apikey' => 'monitor'];
        return new FakeDnsRecords($accounts);
    }

    private function ok(array $result, array $info = []): array
    {
        return [200, [], ['success' => true, 'errors' => [], 'result' => $result] + ($info ? ['result_info' => $info] : [])];
    }

    private function zone(string $name, string $account, string $id = 'z1'): array
    {
        return ['id' => $id, 'name' => $name, 'status' => 'pending', 'account' => ['id' => $account],
            'name_servers' => ['ann.ns.cloudflare.com', 'bob.ns.cloudflare.com']];
    }

    public function testFindsExactZoneOnly(): void
    {
        $client = $this->client();
        $client->responses[] = $this->ok([$this->zone('sub.example.com', 'id-a', 'z0'), $this->zone('example.com', 'id-a')]);
        self::assertSame([
            'state' => 'found', 'zone_id' => 'z1', 'account' => 'a', 'status' => 'pending',
            'name_servers' => ['ann.ns.cloudflare.com', 'bob.ns.cloudflare.com'],
        ], $client->findZone('Example.com'));
        self::assertSame(['a'], $client->accountLabels());
        [$token, $method, $path] = $client->calls[0];
        self::assertSame(['token-a', 'GET'], [$token, $method]);
        self::assertStringContainsString('name=example.com', $path);
        self::assertStringContainsString('account.id=id-a', $path);

        $client->responses[] = $this->ok([$this->zone('example.com', 'id-a')]);
        self::assertSame('missing', $client->findZone('sub.example.com')['state']);
    }

    public function testAmbiguousAndIncompleteSearch(): void
    {
        $client = $this->client(['a', 'b', 'c']);
        $client->responses = [
            $this->ok([$this->zone('example.com', 'id-a')]),
            $this->ok([$this->zone('example.com', 'id-b', 'z2')]),
            [500, [], null],
        ];
        $result = $client->findZone('example.com');
        self::assertSame('ambiguous', $result['state']);

        $client->responses = [$this->ok([$this->zone('example.com', 'id-a')]), $this->ok([]), [403, [], [
            'success' => false, 'errors' => [['code' => 10000, 'message' => 'Authentication error']],
        ]]];
        $result = $client->findZone('example.com');
        self::assertSame('error', $result['state']);
        self::assertSame('Поиск неполный: c: Cloudflare 10000: Authentication error', $result['message']);

        $client->responses = [$this->ok([]), $this->ok([]), $this->ok([])];
        self::assertSame('missing', $client->findZone('example.com')['state']);

        self::assertSame('error', (new FakeDnsRecords([]))->findZone('example.com')['state']);
    }

    public function testListReadsAllPages(): void
    {
        $client = $this->client();
        $page = [];
        for ($i = 0; $i < 100; $i++) {
            $page[] = ['id' => "r$i", 'type' => 'A', 'name' => "h$i.a.com", 'content' => '1.1.1.1', 'proxied' => true, 'ttl' => 1];
        }
        // total_count/total_pages may be stale right after a batch: a full page means "read on".
        $client->responses = [
            $this->ok($page, ['page' => 1, 'total_pages' => 1, 'total_count' => 2]),
            $this->ok([['id' => 'mx', 'type' => 'MX', 'name' => 'a.com', 'content' => 'mail.a.com', 'priority' => 10]], ['page' => 2, 'total_pages' => 1]),
        ];
        $records = $client->list('a', 'z1');
        self::assertCount(101, $records);
        self::assertSame(['id' => 'mx', 'type' => 'MX', 'name' => 'a.com', 'content' => 'mail.a.com',
            'priority' => 10, 'proxied' => false, 'ttl' => 1], $records[100]);
        self::assertStringContainsString('zones/z1/dns_records?per_page=100&page=2', $client->calls[1][2]);
    }

    public function testListOfLiveFixture(): void
    {
        $client = $this->client();
        $client->responses[] = [200, [], $this->fixture('cf-geometrydashsgame.com-1-before.json')];
        $records = $client->list('a', 'z1');
        self::assertCount(9, $records);
        self::assertNull($records[0]['priority']);
    }

    public function testBatchSuccess(): void
    {
        $client = $this->client();
        $request = $this->fixture('cf-geometrydashsgame.com-2-batch-request.json');
        $client->responses[] = [200, [], $this->fixture('cf-geometrydashsgame.com-3-batch-response.json')];
        $result = $client->batch('a', 'z1', array_column($request['deletes'], 'id'), $request['posts']);
        self::assertCount(2, $result['deletes']);
        self::assertCount(9, $result['posts']);
        [, $method, $path, $body] = $client->calls[0];
        self::assertSame(['POST', 'zones/z1/dns_records/batch'], [$method, $path]);
        self::assertSame($request, $body);
    }

    public function testBatchErrorAndEmptyParts(): void
    {
        $client = $this->client();
        $client->responses[] = [400, [], $this->fixture('cf-batch-invalid-response.json')];
        try {
            $client->batch('a', 'z1', [], [['type' => 'A', 'name' => 'broken.a.com', 'content' => 'not-an-ip']]);
            self::fail('Exception expected');
        } catch (\RuntimeException $exception) {
            self::assertStringStartsWith('Cloudflare 9005: failed to POST record', $exception->getMessage());
        }
        self::assertArrayNotHasKey('deletes', $client->calls[0][3]);
    }

    public function testRetryAfterOnGet(): void
    {
        $client = $this->client();
        $client->responses = [[429, ['retry-after' => '3'], null], $this->ok([])];
        self::assertSame('missing', $client->findZone('a.com')['state']);
        self::assertSame([3], $client->sleeps);
        self::assertCount(2, $client->calls);

        $client->responses = [[429, ['retry-after' => '120'], null], $this->ok([])];
        $client->findZone('a.com');
        self::assertSame([3, 30], $client->sleeps);

        // Only one retry.
        $client->responses = [[429, [], null], [429, [], null], $this->ok([])];
        self::assertSame('error', $client->findZone('a.com')['state']);
        self::assertSame([3, 30, 5], $client->sleeps);
    }

    public function testNoRetryForBatch(): void
    {
        $client = $this->client();
        $client->responses = [[429, ['retry-after' => '1'], null], $this->ok(['deletes' => [], 'posts' => []])];
        $this->expectExceptionMessage('Rate limit Cloudflare, batch не применён');
        try {
            $client->batch('a', 'z1', ['x'], []);
        } finally {
            self::assertSame([], $client->sleeps);
            self::assertCount(1, $client->calls);
        }
    }

    public function testBatchTimeout(): void
    {
        $client = $this->client();
        $client->responses[] = new RequestTimeout('timeout');
        $this->expectException(BatchTimeout::class);
        $client->batch('a', 'z1', ['x'], []);
    }
}
