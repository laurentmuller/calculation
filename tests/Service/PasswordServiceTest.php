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

final class PasswordServiceTest extends TestCase
{
    use TranslatorMockTrait;

    public static function getScores(): \Generator
    {
        yield ['', StrengthLevel::VERY_WEAK];
        yield ['123456', StrengthLevel::VERY_WEAK];
        yield ['49898*962ZService', StrengthLevel::VERY_STRONG];
    }

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

    #[DataProvider('getScores')]
    public function testGetScore(string $password, StrengthLevel $expected): void
    {
        $service = $this->createService();
        $query = new PasswordQuery($password, $expected);
        $actual = $service->getScore($query);
        self::assertSame($expected, $actual);
    }

    public function testInvalidScore(): void
    {
        $zxcvbn = $this->createMock(Zxcvbn::class);
        $zxcvbn->method('passwordStrength')
            ->willReturn([
                'score' => -10,
                'feedback' => [
                    'warning' => '',
                    'suggestions' => [],
                ],
            ]);
        $factory = $this->createMock(ZxcvbnFactoryInterface::class);
        $factory->method('createZxcvbn')
            ->willReturn($zxcvbn);
        $service = $this->createService($factory);

        $query = new PasswordQuery('123456', StrengthLevel::STRONG);
        $actual = $service->validate($query);
        self::assertArrayHasKey('result', $actual);
        self::assertFalse($actual['result']);
    }

    #[DataProvider('getValidations')]
    public function testValidate(string $password, StrengthLevel $level, bool $expected): void
    {
        $service = $this->createService();
        $query = new PasswordQuery($password, $level);
        $actual = $service->validate($query);
        self::assertArrayHasKey('result', $actual);
        self::assertSame($expected, $actual['result']);
    }

    private function createService(?ZxcvbnFactoryInterface $factory = null): PasswordService
    {
        $translator = $this->createMockTranslator();
        $factory ??= new ZxcvbnFactory([], $translator);

        return new PasswordService($factory, $translator);
    }
}
