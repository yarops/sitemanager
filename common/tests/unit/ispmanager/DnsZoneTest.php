<?php

namespace common\tests\unit\ispmanager;

use common\components\ispmanager\DnsZone;
use common\components\ispmanager\ZoneNotFound;
use common\models\Server;
use PHPUnit\Framework\TestCase;

class FakeDnsZone extends DnsZone
{
    public ?string $body = null;
    public array $calls = [];

    protected function request(Server $server, array $params): string
    {
        $this->calls[] = $params;
        if ($this->body === null) {
            throw new \RuntimeException('Simulated transport failure with secret details');
        }
        return $this->body;
    }
}

class DnsZoneTest extends TestCase
{
    private function fixture(string $name): string
    {
        return file_get_contents(dirname(__DIR__) . '/dnssync/fixtures/' . $name);
    }

    public function testParsesTemplateZone(): void
    {
        $records = DnsZone::parse($this->fixture('isp-domain.record-dear-passenger.com.json'), 'dear-passenger.com');
        self::assertCount(12, $records);
        self::assertSame(
            ['type' => 'A', 'name' => 'dear-passenger.com.', 'content' => '5.11.83.189', 'priority' => null],
            $records[0]
        );
        $mx = array_values(array_filter($records, static fn(array $r): bool => $r['type'] === 'MX'));
        self::assertSame([10, 20], array_column($mx, 'priority'));
        self::assertSame('mail.dear-passenger.com.', $mx[0]['content']);
        $txt = array_values(array_filter($records, static fn(array $r): bool => $r['type'] === 'TXT'));
        self::assertSame('v=spf1 ip4:5.11.83.189 a mx ~all', $txt[0]['content']);
    }

    public function testRecordsRequestsZoneByElid(): void
    {
        $zone = new FakeDnsZone();
        $zone->body = $this->fixture('isp-domain.record-dear-passenger.com.json');
        self::assertCount(12, $zone->records(new Server(), 'dear-passenger.com'));
        self::assertSame([['func' => 'domain.record', 'elid' => 'dear-passenger.com']], $zone->calls);
    }

    public function testMissingZoneHasOwnException(): void
    {
        $this->expectException(ZoneNotFound::class);
        DnsZone::parse($this->fixture('isp-domain.record-missing-zone.json'), 'no-such-zone-xyz.com');
    }

    public function testPlidErrorIsReported(): void
    {
        // The same "missed domain" error: requesting by plid finds no zone at all.
        $this->expectException(ZoneNotFound::class);
        $this->expectExceptionMessage("Domain name with  '' does not exist");
        DnsZone::parse($this->fixture('isp-domain.record-plid-error.json'), 'dear-passenger.com');
    }

    public function testOtherPanelErrors(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ISPmanager: Access denied');
        DnsZone::parse(json_encode(['doc' => ['error' => ['$type' => 'access', 'msg' => ['$' => 'Access denied']]]]), 'a.com');
    }

    public function testRejectsInvalidResponse(): void
    {
        $this->expectExceptionMessage('Некорректный ответ ISPmanager');
        DnsZone::parse('<html>', 'a.com');
    }

    public function testRejectsIncompletePage(): void
    {
        $this->expectExceptionMessage('Зона не помещается в одну страницу');
        DnsZone::parse(json_encode(['doc' => ['elem' => [], 'p_cnt' => ['$' => '1000'], 'p_elems' => ['$' => '1001']]]), 'a.com');
    }

    public function testRejectsAnotherZone(): void
    {
        $this->expectExceptionMessage('другой зоны');
        DnsZone::parse($this->fixture('isp-domain.record-dear-passenger.com.json'), 'other.com');
    }

    public function testMxWithoutPriorityIsAnError(): void
    {
        $this->expectExceptionMessage('без priority');
        DnsZone::parse(json_encode(['doc' => ['elem' => [[
            'name' => ['$' => 'a.com.'], 'rtype_hidden' => ['$' => 'MX'], 'value' => ['$' => 'mail.a.com.'],
        ]]]]), 'a.com');
    }

    public function testEmptyZoneAndSingleElement(): void
    {
        self::assertSame([], DnsZone::parse(json_encode(['doc' => ['p_elems' => ['$' => '0']]]), 'a.com'));
        $records = DnsZone::parse(json_encode(['doc' => ['elem' => [
            'name' => ['$' => 'a.com.'], 'rtype_hidden' => ['$' => 'A'], 'value' => ['$' => '1.2.3.4'],
        ]]]), 'a.com');
        self::assertSame('1.2.3.4', $records[0]['content']);
    }

    public function testTransportErrorsAreNotLeaked(): void
    {
        $zone = new FakeDnsZone();
        try {
            $zone->records(new Server(), 'a.com');
            self::fail('Exception expected');
        } catch (\RuntimeException $exception) {
            self::assertSame('Ошибка запроса к ISPmanager', $exception->getMessage());
        }
    }
}
