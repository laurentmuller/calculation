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
use App\Enums\Theme;
use App\Tests\DateAssertTrait;
use PHPUnit\Framework\TestCase;

class AbstractPropertyTest extends TestCase
{
    use DateAssertTrait;
    use IdTrait;

    /**
     * @throws \ReflectionException
     */
    public function testArray(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->getArray());
        $expected = [1, 'string', true];
        $entity->setArray($expected);
        $actual = $entity->getArray();
        // @phpstan-ignore staticMethod.impossibleType
        self::assertIsArray($actual);
        // @phpstan-ignore staticMethod.impossibleType
        self::assertCount(3, $actual);
        // @phpstan-ignore staticMethod.impossibleType
        self::assertSame($expected, $actual);

        $entity->setValue('{invalidJson');
        self::assertNull($entity->getArray());
    }

    /**
     * @throws \ReflectionException
     */
    public function testBoolean(): void
    {
        $entity = $this->getEntity();
        self::assertFalse($entity->getBoolean());
        $entity->setBoolean(true);
        self::assertTrue($entity->getBoolean());
    }

    /**
     * @throws \ReflectionException
     */
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

    /**
     * @throws \ReflectionException
     */
    public function testDate(): void
    {
        $date = new \DateTimeImmutable();
        $entity = $this->getEntity();
        self::assertNull($entity->getDate());
        $entity->setDate($date);
        $actual = $entity->getDate();
        // @phpstan-ignore staticMethod.impossibleType
        self::assertNotNull($actual);
        self::assertSameDate($date, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testFloat(): void
    {
        $entity = $this->getEntity();
        self::assertSame(0.0, $entity->getFloat());
        $entity->setFloat(1);
        self::assertSame(1.0, $entity->getFloat());
    }

    /**
     * @throws \ReflectionException
     */
    public function testInteger(): void
    {
        $entity = $this->getEntity();
        self::assertSame(0, $entity->getInteger());
        $entity->setInteger(1);
        self::assertSame(1, $entity->getInteger());
    }

    /**
     * @throws \ReflectionException
     */
    public function testString(): void
    {
        $entity = $this->getEntity();
        self::assertNull($entity->getValue());
        $entity->setString('string');
        self::assertSame('string', $entity->getValue());
        $entity->setString(null);
        self::assertNull($entity->getValue());
    }

    /**
     * @throws \ReflectionException
     */
    public function testValue(): void
    {
        $entity = $this->getEntity();

        $entity->setValue(true);
        self::assertTrue($entity->getBoolean());

        $entity->setValue(1);
        self::assertSame(1, $entity->getInteger());

        $array = [1, 'string', true];
        $entity->setValue($array);
        self::assertSame($array, $entity->getArray());

        $date = new \DateTimeImmutable();
        $entity->setValue($date);
        $actual = $entity->getDate();
        self::assertNotNull($actual);
        self::assertSameDate($date, $actual);

        $category = new Category();
        self::setId($category, 10);
        $entity->setValue($category);
        self::assertSame(10, $entity->getInteger());

        $theme = Theme::AUTO;
        $entity->setValue($theme);
        self::assertSame($theme->value, $entity->getValue());

        $permission = EntityPermission::ADD;
        $entity->setValue($permission);
        self::assertSame($permission->value, $entity->getInteger());

        $entity->setValue(null);
        self::assertSame('', $entity->getValue());
    }

    /**
     * @throws \ReflectionException
     */
    private function getEntity(?int $id = null): AbstractProperty
    {
        $entity = new class() extends AbstractProperty {};
        if (\is_int($id)) {
            return self::setId($entity, $id);
        }

        return $entity;
    }
}
