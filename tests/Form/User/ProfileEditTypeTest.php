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

use App\Entity\User;
use App\Form\User\ProfileEditType;
use App\Tests\Form\EntityTypeTestCase;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;

/**
 * @extends EntityTypeTestCase<User, ProfileEditType>
 */
#[CoversClass(ProfileEditType::class)]
class ProfileEditTypeTest extends EntityTypeTestCase
{
    use TranslatorMockTrait;
    use VichImageTypeTrait;

    protected function getData(): array
    {
        return [
            'username' => 'username',
            'email' => 'email@email.com',
            'imageFile' => null,
        ];
    }

    protected function getEntityClass(): string
    {
        return User::class;
    }

    protected function getFormTypeClass(): string
    {
        return ProfileEditType::class;
    }

    /**
     * @throws Exception
     */
    protected function getPreloadedExtensions(): array
    {
        return [
            $this->createVichImageType(),
        ];
    }
}