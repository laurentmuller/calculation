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

use App\Entity\AbstractEntity;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(AbstractEntity::class)]
class AbstractEntityTest extends TestCase
{
    use IdTrait;

    public static function getTrims(): \Generator
    {
        yield [null, null];
        yield ['', null];
        yield [' ', null];
        yield ['content', 'content'];
        yield [' content', 'content'];
        yield ['content ', 'content'];
        yield [' content ', 'content'];
    }

    /**
     * @throws \ReflectionException
     */
    public function testClone(): void
    {
        $entity = $this->getEntity(1);
        self::assertSame(1, $entity->getId());

        $clone = clone $entity;
        self::assertNull($clone->getId());
    }

    /**
     * @throws \ReflectionException
     */
    public function testDisplay(): void
    {
        $entity = $this->getEntity();
        self::assertSame('0', $entity->getDisplay());
        self::assertSame('0', $entity->__toString());

        $entity = $this->getEntity(10);
        self::assertSame('10', $entity->getDisplay());
        self::assertSame('10', $entity->__toString());
    }

    /**
     * @throws \ReflectionException
     */
    public function testIsNew(): void
    {
        $entity = $this->getEntity();
        self::assertTrue($entity->isNew());

        $entity = $this->getEntity(0);
        self::assertTrue($entity->isNew());

        $entity = $this->getEntity(10);
        self::assertFalse($entity->isNew());
    }

    /**
     * @throws \ReflectionException
     *
     * @psalm-suppress InaccessibleMethod
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getTrims')]
    public function testTrim(?string $value, ?string $expected): void
    {
        $entity = $this->getEntity();
        // @phpstan-ignore-next-line
        $actual = $entity->trim($value);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    private function getEntity(?int $id = null): AbstractEntity
    {
        $entity = new class() extends AbstractEntity {
            public function trim(?string $str): ?string
            {
                return parent::trim($str);
            }
        };
        if (\is_int($id)) {
            return $this->setId($entity, $id);
        }

        return $entity;
    }
}
