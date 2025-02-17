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

namespace App\Tests\Form\Group;

use App\Entity\Group;
use App\Form\Group\GroupType;
use App\Tests\Form\EntityTypeTestCase;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @extends EntityTypeTestCase<Group, GroupType>
 */
class GroupTypeTest extends EntityTypeTestCase
{
    #[\Override]
    protected function getData(): array
    {
        return [
            'code' => 'code',
            'description' => 'description',
            'margins' => new ArrayCollection(),
        ];
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return Group::class;
    }

    #[\Override]
    protected function getFormTypeClass(): string
    {
        return GroupType::class;
    }
}
