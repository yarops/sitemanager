<?php

namespace common\tests\unit\models;

use common\models\Item;
use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase
{
    public function testPublishDateIsSetWhenEmptyFormValueIsPublished(): void
    {
        $item = $this->createItem();
        $item->setOldAttributes(['publish_status' => Item::STATUS_DRAFT]);
        $item->publish_status = Item::STATUS_PUBLISH;
        $item->publish_date = '';

        $before = time();
        $this->assertTrue($item->runBeforeSave(false));

        $this->assertNotNull($item->publish_date);
        $this->assertGreaterThanOrEqual($before, strtotime($item->publish_date));
        $this->assertLessThanOrEqual(time(), strtotime($item->publish_date));
    }

    public function testDraftEmptyPublishDateIsNormalizedToNull(): void
    {
        $item = $this->createItem();
        $item->publish_status = Item::STATUS_DRAFT;
        $item->publish_date = '';

        $this->assertTrue($item->runBeforeSave(true));
        $this->assertNull($item->publish_date);
    }

    public function testExistingPublishDateIsPreserved(): void
    {
        $publishDate = '2026-07-01 12:34:56';
        $item = $this->createItem();
        $item->setOldAttributes(['publish_status' => Item::STATUS_PUBLISH]);
        $item->publish_status = Item::STATUS_PUBLISH;
        $item->publish_date = $publishDate;

        $this->assertTrue($item->runBeforeSave(false));
        $this->assertSame($publishDate, $item->publish_date);
    }

    public function testDemoPreservesSettingsAndClearsSchedule(): void
    {
        $item = $this->createItem();
        $item->setOldAttributes(['publish_status' => Item::STATUS_PUBLISH]);
        $item->publish_status = Item::STATUS_DEMO;
        $item->check_enabled = 1;
        $item->check_interval = 15;
        $item->notify_strategy = Item::NOTIFY_IMMEDIATE;
        $item->next_check_at = '2026-07-01 12:00:00';
        $item->publish_date = '2026-07-01 11:00:00';

        $this->assertTrue($item->runBeforeSave(false));
        $this->assertNull($item->next_check_at);
        $this->assertFalse($item->canMonitor());
        $this->assertSame(1, $item->check_enabled);
        $this->assertSame(15, $item->check_interval);
        $this->assertSame(Item::NOTIFY_IMMEDIATE, $item->notify_strategy);
        $this->assertSame('2026-07-01 11:00:00', $item->publish_date);
    }

    /** @dataProvider publicationMonitoringCases */
    public function testPublishingDemoSchedulesOnlyEligibleSites(int $enabled, int $archived, bool $scheduled): void
    {
        $item = $this->createItem();
        $item->setOldAttributes(['publish_status' => Item::STATUS_DEMO]);
        $item->publish_status = Item::STATUS_PUBLISH;
        $item->check_enabled = $enabled;
        $item->is_archived = $archived;
        $item->publish_date = '';
        $before = time();

        $this->assertTrue($item->runBeforeSave(false));
        $this->assertNotNull($item->publish_date);
        $this->assertSame($scheduled, $item->canMonitor());
        if ($scheduled) {
            $this->assertGreaterThanOrEqual($before, strtotime($item->next_check_at));
            $this->assertLessThanOrEqual(time(), strtotime($item->next_check_at));
        } else {
            $this->assertNull($item->next_check_at);
        }
    }

    public function publicationMonitoringCases(): array
    {
        return [[1, 0, true], [0, 0, false], [1, 1, false]];
    }

    public function testNewDemoDoesNotPublishOrSchedule(): void
    {
        $item = $this->createItem();
        $item->publish_status = Item::STATUS_DEMO;
        $item->publish_date = '';
        $item->check_enabled = 1;
        $this->assertTrue($item->runBeforeSave(true));
        $this->assertNull($item->publish_date);
        $this->assertNull($item->next_check_at);
    }

    public function testPublicationStatusValidation(): void
    {
        $item = $this->createItem();
        foreach ([Item::STATUS_DRAFT, Item::STATUS_PUBLISH, Item::STATUS_DEMO] as $status) {
            $item->publish_status = $status;
            $this->assertTrue($item->validate(['publish_status']));
        }
        $item->publish_status = 'unknown';
        $this->assertFalse($item->validate(['publish_status']));
    }

    private function createItem(): Item
    {
        return new class extends Item {
            public function attributes(): array
            {
                return ['domain', 'protocol', 'publish_status', 'publish_date', 'updated_at', 'check_enabled', 'check_interval', 'notify_strategy', 'is_archived', 'next_check_at'];
            }

            public function runBeforeSave(bool $insert): bool
            {
                return $this->beforeSave($insert);
            }
        };
    }
}
