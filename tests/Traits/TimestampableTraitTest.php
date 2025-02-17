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

    #[\Override]
    protected function setUp(): void
    {
        $this->createdAt = null;
        $this->createdBy = null;
        $this->updatedAt = null;
        $this->updatedBy = null;
    }

    #[\Override]
    public function getDisplay(): string
    {
        return 'Fake';
    }

    #[\Override]
    public function getId(): ?int
    {
        return null;
    }

    #[\Override]
    public function isNew(): bool
    {
        return true;
    }

    public function testCreatedMessageEmpty(): void
    {
        $message = $this->getCreatedMessage();
        $actual = $message->getMessage();
        self::assertSame('common.entity_created', $actual);

        $actual = $message->getParameters();
        self::assertCount(1, $actual);
        self::assertArrayHasKey('%content%', $actual);

        $actual = $this->getCreatedMessage(true)->getMessage();
        self::assertSame('common.entity_empty', $actual);
    }

    public function testCreatedMessageWithValues(): void
    {
        $this->createdBy = null;
        $this->createdAt = new \DateTimeImmutable();
        $message = $this->getCreatedMessage();
        $actual = $message->getMessage();
        self::assertSame('common.entity_created', $actual);
        $actual = $message->getParameters();
        self::assertCount(1, $actual);

        $this->createdBy = 'user';
        $this->createdAt = new \DateTimeImmutable();
        $message = $this->getCreatedMessage();
        $actual = $message->getMessage();
        self::assertSame('common.entity_created', $actual);
        $actual = $message->getParameters();
        self::assertCount(1, $actual);

        $this->createdBy = 'user';
        $this->createdAt = null;
        $message = $this->getCreatedMessage();
        $actual = $message->getMessage();
        self::assertSame('common.entity_created', $actual);
        $actual = $message->getParameters();
        self::assertCount(1, $actual);

        $actual = $this->getCreatedMessage(true)->getMessage();
        self::assertSame('common.entity_date_user', $actual);
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
        self::assertCount(1, $actual);
        self::assertArrayHasKey('%content%', $actual);

        $actual = $this->getUpdatedMessage(true)->getMessage();
        self::assertSame('common.entity_empty', $actual);
    }

    public function testUpdatedMessageWithValues(): void
    {
        $this->updatedBy = null;
        $this->updatedAt = new \DateTimeImmutable();
        $message = $this->getUpdatedMessage();
        $actual = $message->getMessage();
        self::assertSame('common.entity_updated', $actual);
        $actual = $message->getParameters();
        self::assertCount(1, $actual);

        $this->updatedBy = 'user';
        $this->updatedAt = new \DateTimeImmutable();
        $actual = $message->getMessage();
        self::assertSame('common.entity_updated', $actual);
        $actual = $message->getParameters();
        self::assertCount(1, $actual);

        $this->updatedBy = 'user';
        $this->updatedAt = null;
        $message = $this->getUpdatedMessage();
        $actual = $message->getMessage();
        self::assertSame('common.entity_updated', $actual);
        $actual = $message->getParameters();
        self::assertCount(1, $actual);

        $actual = $this->getUpdatedMessage(true)->getMessage();
        self::assertSame('common.entity_date_user', $actual);
    }

    public function testUpdateTimestampable(): void
    {
        $user = 'user';
        $date = new \DateTimeImmutable('2024-05-24');
        $actual = $this->updateTimestampable($date, $user);

        self::assertTrue($actual);
        self::assertSame($date, $this->getCreatedAt());
        self::assertSame($user, $this->getCreatedBy());
        self::assertSame($date, $this->getUpdatedAt());
        self::assertSame($user, $this->getUpdatedBy());
    }
}
