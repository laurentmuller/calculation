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

    public function testBoolean(): void
    {
        $entity = $this->getEntity();
        self::assertFalse($entity->asBoolean());
        $entity->withBoolean(true);
        self::assertTrue($entity->asBoolean());
    }

    public function testConstructor(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->id);
        self::assertSame('0', $entity->getDisplay());
        self::assertSame('', $entity->name);
        self::assertFalse($entity->asBoolean());
        self::assertNull($entity->asDatePoint());
        self::assertSame(0, $entity->asInteger());
        self::assertNull($entity->value);

        $entity->withName('name');
        self::assertSame('name', $entity->name);
    }

    public function testDatePoint(): void
    {
        $date = new DatePoint();
        $entity = $this->getEntity();
        self::assertNull($entity->asDatePoint());
        $entity->withDate($date);
        /** @var DatePoint|null $actual */
        $actual = $entity->asDatePoint();
        self::assertNotNull($actual);
        self::assertTimestampEquals($date, $actual);
    }

    public function testFloat(): void
    {
        $entity = $this->getEntity();
        self::assertSame(0.0, $entity->asFloat());
        $entity->withFloat(1.0);
        self::assertSame(1.0, $entity->asFloat());
    }

    public function testInteger(): void
    {
        $entity = $this->getEntity();
        self::assertSame(0, $entity->asInteger());
        $entity->withInteger(1);
        self::assertSame(1, $entity->asInteger());
    }

    public function testIntEnum(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->asIntEnum(EntityPermission::class));
        $entity->withBackedEnum(EntityPermission::ADD);
        /** @var EntityPermission|null $actual */
        $actual = $entity->asIntEnum(EntityPermission::class);
        self::assertSame(EntityPermission::ADD, $actual);
    }

    public function testString(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->value);
        $entity->withString('string');
        self::assertSame('string', $entity->value);
    }

    public function testStringEnum(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->asStringEnum(TableView::class));
        $entity->withBackedEnum(TableView::CUSTOM);
        /** @var TableView|null $actual */
        $actual = $entity->asStringEnum(TableView::class);
        self::assertSame(TableView::CUSTOM, $actual);
    }

    public function testValue(): void
    {
        $entity = $this->getEntity();

        $permission = EntityPermission::ADD;
        $entity->withValue($permission);
        self::assertSame($permission, $entity->asIntEnum(EntityPermission::class));
        self::assertSame($permission->value, $entity->asInteger());

        $view = TableView::CUSTOM;
        $entity->withValue($view);
        self::assertSame($view, $entity->asStringEnum(TableView::class));
        self::assertSame($view->value, $entity->value);

        $entity->withValue(true);
        self::assertTrue($entity->asBoolean());

        $date = new DatePoint();
        $entity->withValue($date);
        $actual = $entity->asDatePoint();
        self::assertNotNull($actual);
        self::assertTimestampEquals($date, $actual);

        $entity->withValue(12);
        self::assertSame(12, $entity->asInteger());

        $entity->withValue(1.5);
        self::assertSame(1.5, $entity->asFloat());

        $category = new Category();
        self::setId($category, 10);
        $entity->withValue($category);
        self::assertSame(10, $entity->asInteger());

        $entity->withValue(null);
        self::assertSame('', $entity->value);
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
