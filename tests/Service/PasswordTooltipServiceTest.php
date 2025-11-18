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

use App\Constraint\Password;
use App\Enums\StrengthLevel;
use App\Parameter\ApplicationParameters;
use App\Parameter\SecurityParameter;
use App\Service\PasswordTooltipService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordTooltipServiceTest extends TestCase
{
    use TranslatorMockTrait;
    private bool $compromisedPassword = false;
    private StrengthLevel $level;

    private ApplicationParameters $parameters;
    private Password $password;
    private TranslatorInterface $translator;

    #[\Override]
    protected function setUp(): void
    {
        $this->password = new Password(letters: false);
        $this->level = StrengthLevel::NONE;
        $this->translator = $this->createMockTranslator();

        $security = $this->createMock(SecurityParameter::class);
        $security->method('getLevel')
            ->willReturnCallback(fn (): StrengthLevel => $this->level);
        $security->method('getPasswordConstraint')
            ->willReturnCallback(fn (): Password => $this->password);
        $security->method('isCompromised')
            ->willReturnCallback(fn (): bool => $this->compromisedPassword);

        $this->parameters = $this->createMock(ApplicationParameters::class);
        $this->parameters->method('getSecurity')
            ->willReturn($security);
    }

    public function testWithCompromisedPassword(): void
    {
        $this->compromisedPassword = true;
        $service = new PasswordTooltipService($this->parameters, $this->translator);
        $actual = $service->getTooltips();
        self::assertCount(1, $actual);
        self::assertSame('password.security_compromised_password', $actual[0]);
    }

    public function testWithDefaultValues(): void
    {
        $service = new PasswordTooltipService($this->parameters, $this->translator);
        $actual = $service->getTooltips();
        self::assertEmpty($actual);
    }

    public function testWithPasswordLetter(): void
    {
        $this->password = new Password(letters: true);
        $service = new PasswordTooltipService($this->parameters, $this->translator);
        $actual = $service->getTooltips();
        self::assertCount(1, $actual);
        self::assertSame('password.security_letters', $actual[0]);
    }

    public function testWithStrengthLevel(): void
    {
        $this->level = StrengthLevel::MEDIUM;
        $service = new PasswordTooltipService($this->parameters, $this->translator);
        $actual = $service->getTooltips();
        self::assertCount(1, $actual);
        self::assertStringStartsWith('password.security_strength_level', $actual[0]);
    }
}
