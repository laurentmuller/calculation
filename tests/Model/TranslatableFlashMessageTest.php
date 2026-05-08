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

namespace App\Tests\Model;

use App\Enums\FlashType;
use App\Model\TranslatableFlashMessage;
use PHPUnit\Framework\TestCase;

final class TranslatableFlashMessageTest extends TestCase
{
    public function testDanger(): void
    {
        self::assertSameFlashType(TranslatableFlashMessage::danger('message'), FlashType::DANGER);
    }

    public function testInfo(): void
    {
        self::assertSameFlashType(TranslatableFlashMessage::info('message'), FlashType::INFO);
    }

    public function testSuccess(): void
    {
        self::assertSameFlashType(TranslatableFlashMessage::success('message'), FlashType::SUCCESS);
    }

    public function testWarning(): void
    {
        self::assertSameFlashType(TranslatableFlashMessage::warning('message'), FlashType::WARNING);
    }

    protected static function assertSameFlashType(TranslatableFlashMessage $message, FlashType $expected): void
    {
        self::assertSame($expected, $message->getType());
    }
}
