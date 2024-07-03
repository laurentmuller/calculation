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

use App\Entity\GroupMargin;
use App\Form\Group\GroupMarginType;
use App\Tests\Form\EntityTypeTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends EntityTypeTestCase<GroupMargin, GroupMarginType>
 */
#[CoversClass(GroupMarginType::class)]
class GroupMarginTypeTest extends EntityTypeTestCase
{
    protected function getData(): array
    {
        return [
            'minimum' => 0.0,
            'maximum' => 1.1,
            'margin' => 0.0,
        ];
    }

    protected function getEntityClass(): string
    {
        return GroupMargin::class;
    }

    protected function getFormTypeClass(): string
    {
        return GroupMarginType::class;
    }
}
