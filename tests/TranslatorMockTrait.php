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

namespace App\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @phpstan-require-extends TestCase
 */
trait TranslatorMockTrait
{
    private function createMockTranslator(?string $message = null): MockObject&TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        if (null !== $message) {
            $translator->expects(self::any())
                ->method('trans')
                ->willReturn($message);
        } else {
            $translator->expects(self::any())
                ->method('trans')
                ->willReturnArgument(0);
        }

        return $translator;
    }
}
