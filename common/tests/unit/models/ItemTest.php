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

    private function createItem(): Item
    {
        return new class extends Item {
            public function attributes(): array
            {
                return ['publish_status', 'publish_date', 'updated_at'];
            }

            public function runBeforeSave(bool $insert): bool
            {
                return $this->beforeSave($insert);
            }
        };
    }
}
