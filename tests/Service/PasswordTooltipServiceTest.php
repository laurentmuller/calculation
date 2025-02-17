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
use App\Service\ApplicationService;
use App\Service\PasswordTooltipService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordTooltipServiceTest extends TestCase
{
    use TranslatorMockTrait;

    private MockObject&ApplicationService $application;
    private bool $compromisedPassword = false;
    private StrengthLevel $level;
    private Password $password;
    private MockObject&TranslatorInterface $translator;

    #[\Override]
    protected function setUp(): void
    {
        $this->password = new Password(letters: false);
        $this->level = StrengthLevel::NONE;
        $this->translator = $this->createMockTranslator();
        $this->application = $this->createMock(ApplicationService::class);
        $this->application->method('getStrengthLevel')
            ->willReturnCallback(fn (): StrengthLevel => $this->level);
        $this->application->method('getPasswordConstraint')
            ->willReturnCallback(fn (): Password => $this->password);
        $this->application->method('isCompromisedPassword')
            ->willReturnCallback(fn (): bool => $this->compromisedPassword);
    }

    public function testWithCompromisedPassword(): void
    {
        $this->compromisedPassword = true;
        $service = new PasswordTooltipService($this->application, $this->translator);
        $actual = $service->getTooltips();
        self::assertCount(1, $actual);
        self::assertSame('password.security_compromised_password', $actual[0]);
    }

    public function testWithDefaultValues(): void
    {
        $service = new PasswordTooltipService($this->application, $this->translator);
        $actual = $service->getTooltips();
        self::assertEmpty($actual);
    }

    public function testWithPasswordLetter(): void
    {
        $this->password = new Password(letters: true);
        $service = new PasswordTooltipService($this->application, $this->translator);
        $actual = $service->getTooltips();
        self::assertCount(1, $actual);
        self::assertSame('password.security_letters', $actual[0]);
    }

    public function testWithStrengthLevel(): void
    {
        $this->level = StrengthLevel::MEDIUM;
        $service = new PasswordTooltipService($this->application, $this->translator);
        $actual = $service->getTooltips();
        self::assertCount(1, $actual);
        self::assertStringStartsWith('password.security_strength_level', $actual[0]);
    }
}
