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

namespace App\Tests\Enums;

use App\Enums\FlashType;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(FlashType::class)]
class FlashTypeTest extends TestCase
{
    private ?TranslatorInterface $translator = null;

    public static function getLabels(): array
    {
        return [
            [FlashType::DANGER, 'flash_bag.danger'],
            [FlashType::INFO, 'flash_bag.info'],
            [FlashType::SUCCESS, 'flash_bag.success'],
            [FlashType::WARNING, 'flash_bag.warning'],
        ];
    }

    public static function getValues(): array
    {
        return [
            [FlashType::DANGER, 'danger'],
            [FlashType::INFO, 'info'],
            [FlashType::SUCCESS, 'success'],
            [FlashType::WARNING, 'warning'],
        ];
    }

    public function testCount(): void
    {
        self::assertCount(4, FlashType::cases());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabels')]
    public function testLabel(FlashType $type, string $expected): void
    {
        self::assertSame($expected, $type->getReadable());
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLabels')]
    public function testTranslate(FlashType $type, string $expected): void
    {
        $translator = $this->createTranslator();
        self::assertSame($expected, $type->trans($translator));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
    public function testValue(FlashType $type, string $expected): void
    {
        self::assertSame($expected, $type->value);
    }

    /**
     * @throws Exception
     */
    private function createTranslator(): TranslatorInterface
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->createMock(TranslatorInterface::class);
            $this->translator->method('trans')
                ->willReturnArgument(0);
        }

        return $this->translator;
    }
}
