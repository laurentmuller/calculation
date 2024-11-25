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

namespace App\Tests\Traits;

use App\Interfaces\TimestampableInterface;
use App\Traits\TimestampableTrait;
use PHPUnit\Framework\TestCase;

class TimestampableTraitTest extends TestCase implements TimestampableInterface
{
    use TimestampableTrait;

    public function __toString(): string
    {
        return $this->getDisplay();
    }

    protected function setUp(): void
    {
        $this->createdAt = null;
        $this->createdBy = null;
        $this->updatedAt = null;
        $this->updatedBy = null;
    }

    public function getDisplay(): string
    {
        return TimestampableInterface::class;
    }

    public function getId(): ?int
    {
        return null;
    }

    public function isNew(): bool
    {
        return true;
    }

    public function testCreatedMessage(): void
    {
        $message = $this->getCreatedMessage();
        $actual = $message->getMessage();
        self::assertSame('common.entity_created', $actual);

        $actual = $message->getParameters();
        self::assertCount(2, $actual);
        self::assertArrayHasKey('%date%', $actual);
        self::assertArrayHasKey('%user%', $actual);

        $actual = $this->getCreatedMessage(true)->getMessage();
        self::assertSame('common.entity_created_short', $actual);
    }

    public function testDefault(): void
    {
        self::assertNull($this->getCreatedAt());
        self::assertNull($this->getCreatedBy());
        self::assertNull($this->getUpdatedAt());
        self::assertNull($this->getUpdatedBy());
    }

    public function testUpdatedMessage(): void
    {
        $message = $this->getUpdatedMessage();
        $actual = $message->getMessage();
        self::assertSame('common.entity_updated', $actual);

        $actual = $message->getParameters();
        self::assertCount(2, $actual);
        self::assertArrayHasKey('%date%', $actual);
        self::assertArrayHasKey('%user%', $actual);

        $actual = $this->getUpdatedMessage(true)->getMessage();
        self::assertSame('common.entity_updated_short', $actual);
    }

    public function testUpdateTimestampable(): void
    {
        $date = new \DateTimeImmutable('2024-05-24');
        $user = 'user';
        $actual = $this->updateTimestampable($date, $user);

        self::assertTrue($actual);
        self::assertSame($date, $this->getCreatedAt());
        self::assertSame($user, $this->getCreatedBy());
        self::assertSame($date, $this->getUpdatedAt());
        self::assertSame($user, $this->getUpdatedBy());
    }
}
