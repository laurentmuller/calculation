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

namespace App\Tests\Service;

use App\Enums\StrengthLevel;
use App\Model\PasswordQuery;
use App\Service\PasswordService;
use App\Tests\TranslatorMockTrait;
use Createnl\ZxcvbnBundle\ZxcvbnFactory;
use Createnl\ZxcvbnBundle\ZxcvbnFactoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Zxcvbn;

class PasswordServiceTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * @phpstan-return \Generator<int, array{string, StrengthLevel, bool}>
     */
    public static function getValidations(): \Generator
    {
        yield ['', StrengthLevel::NONE, false];
        yield ['', StrengthLevel::VERY_WEAK, false];
        yield ['123456', StrengthLevel::VERY_STRONG, false];
        yield ['49898*962ZService', StrengthLevel::NONE, false];

        yield ['49898*962ZService', StrengthLevel::VERY_WEAK, true];
        yield ['49898*962ZService', StrengthLevel::WEAK, true];
        yield ['49898*962ZService', StrengthLevel::MEDIUM, true];
        yield ['49898*962ZService', StrengthLevel::STRONG, true];
        yield ['49898*962ZService', StrengthLevel::VERY_STRONG, true];
    }

    public function testCustom(): void
    {
        $zxcvbn = $this->createMock(Zxcvbn::class);
        $zxcvbn->method('passwordStrength')
            ->willReturn([
                'score' => -10,
                'feedback' => [
                    'warning' => [],
                    'suggestions' => [],
                ],
            ]);
        $factory = $this->createMock(ZxcvbnFactoryInterface::class);
        $factory->method('createZxcvbn')
            ->willReturn($zxcvbn);
        $translator = $this->createMockTranslator();
        $service = new PasswordService($factory, $translator);

        $query = new PasswordQuery('49898*962ZService');
        $actual = $service->validate($query);
        self::assertArrayHasKey('result', $actual);
        self::assertFalse($actual['result']);
    }

    #[DataProvider('getValidations')]
    public function testValidate(string $password, StrengthLevel $level, bool $expected): void
    {
        $translator = $this->createMockTranslator();
        $factory = new ZxcvbnFactory([], $translator);
        $service = new PasswordService($factory, $translator);

        $query = new PasswordQuery($password, $level);
        $actual = $service->validate($query);
        self::assertArrayHasKey('result', $actual);
        self::assertSame($expected, $actual['result']);
    }
}
