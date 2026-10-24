<?php

namespace common\tests\unit\dnssync;

use common\components\dnssync\NameServers;
use PHPUnit\Framework\TestCase;

class NameServersTest extends TestCase
{
    public function testParsesDohAnswer(): void
    {
        // Live dns.google answers (2026-10-24).
        self::assertSame(['savanna.ns.cloudflare.com', 'trace.ns.cloudflare.com'], NameServers::parse(['Status' => 0, 'Answer' => [
            ['name' => 'geometrydashsgame.com.', 'type' => 2, 'TTL' => 21600, 'data' => 'trace.ns.cloudflare.com.'],
            ['name' => 'geometrydashsgame.com.', 'type' => 2, 'TTL' => 21600, 'data' => 'Savanna.NS.cloudflare.com.'],
            ['name' => 'geometrydashsgame.com.', 'type' => 5, 'data' => 'ignored.'],
        ]]));
        self::assertNull(NameServers::parse(['Status' => 3, 'Authority' => [['name' => 'com.', 'type' => 6]]]));
        self::assertSame([], NameServers::parse(['Status' => 0]));
    }

    public function testRejectsErrors(): void
    {
        $this->expectExceptionMessage('Ошибка DNS, код 2');
        NameServers::parse(['Status' => 2]);
    }

    public function testRejectsInvalidResponse(): void
    {
        $this->expectExceptionMessage('Некорректный ответ DNS');
        NameServers::parse(null);
    }
}
