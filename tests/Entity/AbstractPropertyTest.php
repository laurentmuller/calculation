<?php

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\AbstractProperty;
use App\Entity\Category;
use App\Enums\EntityPermission;
use App\Enums\TableView;
use App\Tests\DateAssertTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

final class AbstractPropertyTest extends TestCase
{
    use DateAssertTrait;
    use IdTrait;

    public function testArray(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->getArray());
        $entity->setArray([1, 'string', true]);
        $actual = $entity->getArray();
        self::assertIsArray($actual);
        self::assertCount(3, $actual);
        self::assertSame([1, 'string', true], $actual);

        $entity->setValue('{invalidJson');
        self::assertNull($entity->getArray());
    }

    public function testBackedEnumInt(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->getBackedEnumInt(EntityPermission::class));
        $entity->setBackedEnum(EntityPermission::ADD);
        $actual = $entity->getBackedEnumInt(EntityPermission::class);
        self::assertSame(EntityPermission::ADD, $actual);
    }

    public function testBackedEnumString(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->getBackedEnumString(TableView::class));
        $entity->setBackedEnum(TableView::CUSTOM);
        $actual = $entity->getBackedEnumString(TableView::class);
        self::assertSame(TableView::CUSTOM, $actual);
    }

    public function testBoolean(): void
    {
        $entity = $this->getEntity();
        self::assertFalse($entity->getBoolean());
        $entity->setBoolean(true);
        self::assertTrue($entity->getBoolean());
    }

    public function testConstructor(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->getId());
        self::assertSame('0', $entity->getDisplay());
        self::assertSame('', $entity->getName());
        self::assertNull($entity->getArray());
        self::assertFalse($entity->getBoolean());
        self::assertNull($entity->getDate());
        self::assertSame(0, $entity->getInteger());
        self::assertNull($entity->getValue());

        $entity->setName('name');
        self::assertSame('name', $entity->getName());
    }

    public function testDate(): void
    {
        $date = new DatePoint();
        $entity = $this->getEntity();
        self::assertNull($entity->getDate());
        $entity->setDate($date);
        $actual = $entity->getDate();
        self::assertNotNull($actual);
        self::assertTimestampEquals($date, $actual);
    }

    public function testFloat(): void
    {
        $entity = $this->getEntity();
        self::assertSame(0.0, $entity->getFloat());
        $entity->setFloat(1.0);
        self::assertSame(1.0, $entity->getFloat());
    }

    public function testInteger(): void
    {
        $entity = $this->getEntity();
        self::assertSame(0, $entity->getInteger());
        $entity->setInteger(1);
        self::assertSame(1, $entity->getInteger());
    }

    public function testString(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->getValue());
        $entity->setString('string');
        self::assertSame('string', $entity->getValue());
    }

    public function testValue(): void
    {
        $entity = $this->getEntity();

        $array = [1, 'string', true];
        $entity->setValue($array);
        self::assertSame($array, $entity->getArray());

        $permission = EntityPermission::ADD;
        $entity->setValue($permission);
        self::assertSame($permission, $entity->getBackedEnumInt(EntityPermission::class));
        self::assertSame($permission->value, $entity->getInteger());

        $view = TableView::CUSTOM;
        $entity->setValue($view);
        self::assertSame($view, $entity->getBackedEnumString(TableView::class));
        self::assertSame($view->value, $entity->getValue());

        $entity->setValue(true);
        self::assertTrue($entity->getBoolean());

        $date = new DatePoint();
        $entity->setValue($date);
        $actual = $entity->getDate();
        self::assertNotNull($actual);
        self::assertTimestampEquals($date, $actual);

        $entity->setValue(12);
        self::assertSame(12, $entity->getInteger());

        $entity->setValue(1.5);
        self::assertSame(1.5, $entity->getFloat());

        $category = new Category();
        self::setId($category, 10);
        $entity->setValue($category);
        self::assertSame(10, $entity->getInteger());

        $entity->setValue(null);
        self::assertSame('', $entity->getValue());
    }

    private function getEntity(?int $id = null): AbstractProperty
    {
        $entity = new class extends AbstractProperty {};
        if (\is_int($id)) {
            return self::setId($entity, $id);
        }

        return $entity;
    }
}
