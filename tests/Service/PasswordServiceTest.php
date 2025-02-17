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
use App\Tests\KernelServiceTestCase;
use App\Tests\TranslatorMockTrait;
use Createnl\ZxcvbnBundle\ZxcvbnFactoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use ZxcvbnPhp\Zxcvbn;

class PasswordServiceTest extends KernelServiceTestCase
{
    use TranslatorMockTrait;

    private PasswordService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(PasswordService::class);
    }

    public static function getValidations(): \Iterator
    {
        yield ['', StrengthLevel::NONE, false];
        yield ['49898*962Zbhasajk', StrengthLevel::NONE, false];
        yield ['49898*962Zbhasajk', StrengthLevel::VERY_WEAK, true];
        yield ['49898*962Zbhasajk', StrengthLevel::WEAK, true];
        yield ['49898*962Zbhasajk', StrengthLevel::MEDIUM, true];
        yield ['49898*962Zbhasajk', StrengthLevel::STRONG, true];
        yield ['49898*962Zbhasajk', StrengthLevel::VERY_STRONG, true];

        yield ['', StrengthLevel::VERY_WEAK, false];
        yield ['123456', StrengthLevel::VERY_STRONG, false];
    }

    public function testCustom(): void
    {
        $translator = $this->createMockTranslator();
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

        $service = new PasswordService($factory, $translator);

        $query = new PasswordQuery('49898*962Zbhasajk');
        $actual = $service->validate($query);
        self::assertArrayHasKey('result', $actual);
        self::assertFalse($actual['result']);
    }

    #[DataProvider('getValidations')]
    public function testValidate(string $password, StrengthLevel $level, bool $expected): void
    {
        $query = new PasswordQuery($password, $level);
        $actual = $this->service->validate($query);
        self::assertArrayHasKey('result', $actual);
        self::assertSame($expected, $actual['result']);
    }
}
