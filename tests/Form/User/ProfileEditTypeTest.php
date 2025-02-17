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

/**
 * @extends EntityTypeTestCase<User, ProfileEditType>
 */
class ProfileEditTypeTest extends EntityTypeTestCase
{
    use VichImageTypeTrait;

    #[\Override]
    protected function getData(): array
    {
        return [
            'username' => 'username',
            'email' => 'email@email.com',
            'imageFile' => null,
        ];
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return User::class;
    }

    #[\Override]
    protected function getFormTypeClass(): string
    {
        return ProfileEditType::class;
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        return [
            $this->createVichImageType(),
        ];
    }
}
