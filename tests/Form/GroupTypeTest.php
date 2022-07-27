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

namespace App\Tests\Form;

use App\Entity\Group;
use App\Form\Group\GroupType;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Test for the {@link GroupType} class.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class GroupTypeTest extends AbstractEntityTypeTestCase
{
    protected function getData(): array
    {
        return [
            'code' => 'code',
            'description' => 'description',
            'margins' => new ArrayCollection(),
            // 'categories' => new ArrayCollection(),
        ];
    }

    protected function getEntityClass(): string
    {
        return Group::class;
    }

    protected function getFormTypeClass(): string
    {
        return GroupType::class;
    }
}
