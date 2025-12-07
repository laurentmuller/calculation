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

namespace App\Tests\Repository;

use App\Entity\GroupMargin;
use App\Repository\GroupMarginRepository;
use App\Tests\EntityTrait\GroupTrait;

/**
 * @extends AbstractRepositoryTestCase<GroupMargin, GroupMarginRepository>
 */
final class GroupMarginRepositoryTest extends AbstractRepositoryTestCase
{
    use GroupTrait;

    public function testGetMargin(): void
    {
        $group = $this->getGroup();
        $actual = $this->repository->getMargin($group, 0.0);
        self::assertSame(1.1, $actual);
    }

    #[\Override]
    protected function getRepositoryClass(): string
    {
        return GroupMarginRepository::class;
    }
}
