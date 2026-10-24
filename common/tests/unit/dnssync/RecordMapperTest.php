<?php

namespace common\tests\unit\dnssync;

use common\components\dnssync\RecordMapper;
use common\components\ispmanager\DnsZone;
use PHPUnit\Framework\TestCase;

class RecordMapperTest extends TestCase
{
    private const CONFIG = [
        'types' => ['A', 'AAAA', 'CNAME', 'MX', 'TXT'],
        'proxied' => ['A' => true, 'AAAA' => true, 'CNAME' => true],
        'proxiedExclude' => ['mail', 'smtp', 'pop', 'imap', 'ftp'],
        'ttl' => 1,
        'maxBatchOperations' => 200,
    ];

    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__ . '/fixtures/' . $name);
    }

    private function record(string $type, string $name, string $content, ?int $priority = null): array
    {
        return ['type' => $type, 'name' => $name, 'content' => $content, 'priority' => $priority];
    }

    public function testTemplateZoneMatchesLiveBatchRequest(): void
    {
        // The live spike batch was built for geometrydashsgame.com from the same ISP template.
        $body = str_replace('dear-passenger.com', 'geometrydashsgame.com', $this->fixture('isp-domain.record-dear-passenger.com.json'));
        $records = DnsZone::parse($body, 'geometrydashsgame.com');
        $expected = json_decode($this->fixture('cf-geometrydashsgame.com-2-batch-request.json'), true)['posts'];
        self::assertEquals($expected, RecordMapper::toCloudflare($records, 'geometrydashsgame.com', self::CONFIG));
    }

    public function testProxiedRules(): void
    {
        $posts = RecordMapper::toCloudflare([
            $this->record('A', 'a.com.', '1.1.1.1'),
            $this->record('AAAA', 'www.a.com.', '::1'),
            $this->record('A', 'imap.a.com.', '1.1.1.1'),
            $this->record('A', 'mx1.a.com.', '1.1.1.1'),
            $this->record('A', 'mail.shop.a.com.', '1.1.1.1'),
            $this->record('CNAME', 'smtp.a.com.', 'mail.a.com.'),
            $this->record('CNAME', 'mx1.a.com.', 'a.com.'),
            $this->record('MX', 'a.com.', 'mx1.a.com.', 5),
            $this->record('TXT', 'a.com.', 'v=spf1 -all'),
            $this->record('NS', 'a.com.', 'ns1.example.com.'),
        ], 'a.com', self::CONFIG);
        $proxied = [];
        foreach ($posts as $post) {
            $proxied[$post['type'] . ' ' . $post['name']] = $post['proxied'] ?? null;
        }
        self::assertSame([
            'A a.com' => true,
            'AAAA www.a.com' => true,
            'A imap.a.com' => false,          // proxiedExclude
            'A mx1.a.com' => false,           // MX target
            'A mail.shop.a.com' => true,      // exclusions match the whole relative name only
            'CNAME smtp.a.com' => true,       // CNAME is always proxied
            'CNAME mx1.a.com' => true,
            'MX a.com' => null,
            'TXT a.com' => null,
        ], $proxied);
        self::assertSame(['type' => 'MX', 'name' => 'a.com', 'ttl' => 1, 'content' => 'mx1.a.com', 'priority' => 5], $posts[7]);
        self::assertSame('mail.a.com', $posts[5]['content']);
    }

    public function testNamesAreNormalized(): void
    {
        self::assertSame('www.example.com', RecordMapper::normalizeName('WWW.Example.COM.'));
        self::assertSame('www.xn--e1afmkfd.xn--p1ai', RecordMapper::normalizeName('www.пример.рф.'));
        self::assertSame('*.a.com', RecordMapper::normalizeName('*.A.com.'));
        self::assertSame('_dmarc.a.com', RecordMapper::normalizeName('_dmarc.a.com.'));
        $posts = RecordMapper::toCloudflare([$this->record('A', 'WWW.пример.рф.', '1.1.1.1')], 'пример.рф', self::CONFIG);
        self::assertSame('www.xn--e1afmkfd.xn--p1ai', $posts[0]['name']);
    }

    public function testRecordOutsideZoneIsRejected(): void
    {
        $this->expectExceptionMessage('вне зоны');
        RecordMapper::toCloudflare([$this->record('A', 'notexample.com.', '1.1.1.1')], 'example.com', self::CONFIG);
    }

    public function testTxtTransportForm(): void
    {
        self::assertSame('v=spf1 a mx ~all', RecordMapper::normalizeTxt('v=spf1 a mx ~all'));
        self::assertSame('v=spf1 a mx ~all', RecordMapper::normalizeTxt('"v=spf1 a mx ~all"'));
        self::assertSame('v=DKIM1; p=AAAABBBB', RecordMapper::normalizeTxt('"v=DKIM1; p=AAAA" "BBBB"'));
        self::assertSame('say "hi"', RecordMapper::normalizeTxt('"say \"hi\""'));
        // Not a quoted form: kept as is.
        self::assertSame('"a" b', RecordMapper::normalizeTxt('"a" b'));
    }

    public function testLegacyZoneDetection(): void
    {
        $legacy = DnsZone::parse($this->fixture('isp-domain.record-franbowgame.com.json'), 'franbowgame.com');
        self::assertTrue(RecordMapper::isLegacy($legacy, 'franbowgame.com'));
        $template = DnsZone::parse($this->fixture('isp-domain.record-dear-passenger.com.json'), 'dear-passenger.com');
        self::assertFalse(RecordMapper::isLegacy($template, 'dear-passenger.com'));
    }

    public function testFilterKeepsConfiguredTypes(): void
    {
        $records = DnsZone::parse($this->fixture('isp-domain.record-dear-passenger.com.json'), 'dear-passenger.com');
        self::assertCount(9, RecordMapper::filter($records, self::CONFIG['types']));
    }

    public function testDisplayLines(): void
    {
        $before = json_decode($this->fixture('cf-geometrydashsgame.com-1-before.json'), true)['result'];
        $before[] = ['type' => 'TXT', 'name' => '_x.geometrydashsgame.com', 'content' => '"quoted"', 'proxied' => false];
        self::assertSame([
            'A @ → 5.11.83.189 [proxied]',
            'A ftp → 5.11.83.189 [DNS only]',
            'A mail → 5.11.83.189 [DNS only]',
            'A pop → 5.11.83.189 [DNS only]',
            'A smtp → 5.11.83.189 [DNS only]',
            'A www → 5.11.83.189 [proxied]',
            'MX @ → mail.geometrydashsgame.com (10)',
            'MX @ → mail.geometrydashsgame.com (20)',
            'TXT @ → v=spf1 ip4:5.11.83.189 a mx ~all',
            'TXT _x → quoted',
        ], RecordMapper::formatForDisplay($before, 'geometrydashsgame.com'));
        // ISP records carry no proxied flag.
        self::assertSame(['MX @ → mail.a.com (10)'], RecordMapper::formatForDisplay(
            [$this->record('MX', 'a.com.', 'mail.a.com.', 10)], 'a.com'
        ));
    }
}
