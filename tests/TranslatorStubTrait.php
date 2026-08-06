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

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @phpstan-require-extends TestCase
 */
trait TranslatorStubTrait
{
    protected function createStubTranslator(?string $message = null): Stub&TranslatorInterface
    {
        $translator = self::createStub(TranslatorInterface::class);
        if (null !== $message) {
            $translator->method('trans')
                ->willReturn($message);
        } else {
            $translator->method('trans')
                ->willReturnArgument(0);
        }

        return $translator;
    }
}
