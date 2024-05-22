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

use PHPUnit\Framework\MockObject\Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

trait TranslatorMockTrait
{
    private function createTranslator(?string $message = null): TranslatorInterface
    {
        try {
            $translator = $this->createMock(TranslatorInterface::class);
            if (null !== $message) {
                $translator->expects($this->any())
                    ->method('trans')
                    ->willReturn($message);
            } else {
                $translator->expects($this->any())
                    ->method('trans')
                    ->willReturnArgument(0);
            }

            return $translator;
        } catch (Exception $e) {
            self::fail($e->getMessage());
        }
    }
}
