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

namespace App\Tests\Form\User;

use App\Constraint\Strength;
use App\Entity\User;
use App\Enums\StrengthLevel;
use App\Form\User\ProfilePasswordType;
use App\Service\ApplicationService;
use App\Tests\Form\EntityTypeTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;

/**
 * @extends EntityTypeTestCase<User, ProfilePasswordType>
 */
class ProfilePasswordTypeTest extends EntityTypeTestCase
{
    use PasswordHasherExtensionTrait;
    use ValidatorExtensionTrait;

    private MockObject&ApplicationService $application;

    protected function setUp(): void
    {
        $this->application = $this->createMock(ApplicationService::class);
        $this->application->method('getStrengthConstraint')
            ->willReturn(new Strength(StrengthLevel::NONE));
        parent::setUp();
    }

    protected function getData(): array
    {
        return [
            'username' => 'username',
        ];
    }

    protected function getEntityClass(): string
    {
        return User::class;
    }

    /**
     * @throws \Exception
     */
    protected function getExtensions(): array
    {
        return \array_merge(parent::getExtensions(), [
            $this->getPasswordHasherExtension(),
            $this->getValidatorExtension(),
        ]);
    }

    protected function getFormTypeClass(): string
    {
        return ProfilePasswordType::class;
    }

    protected function getPreloadedExtensions(): array
    {
        return [
            new ProfilePasswordType($this->application),
        ];
    }
}
