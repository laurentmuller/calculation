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
use App\Tests\TranslatorMockTrait;
use App\Traits\TimestampableTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(TimestampableTrait::class)]
class TimestampableTraitTest extends TestCase implements TimestampableInterface
{
    use TimestampableTrait;
    use TranslatorMockTrait;

    private TranslatorInterface $translator;

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
        $this->translator = $this->createMockTranslator();
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

    public function testCreatedText(): void
    {
        $actual = $this->getCreatedText($this->translator);
        self::assertSame('common.entity_created', $actual);

        $actual = $this->getCreatedText($this->translator, true);
        self::assertSame('common.entity_created_short', $actual);
    }

    public function testDefault(): void
    {
        self::assertNull($this->getCreatedAt());
        self::assertNull($this->getCreatedBy());
        self::assertNull($this->getUpdatedAt());
        self::assertNull($this->getUpdatedBy());
    }

    public function testUpdatedText(): void
    {
        $actual = $this->getUpdatedText($this->translator);
        self::assertSame('common.entity_updated', $actual);

        $actual = $this->getUpdatedText($this->translator, true);
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
