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

use App\Repository\GroupMarginRepository;
use App\Tests\DatabaseTrait;
use App\Tests\EntityTrait\GroupTrait;
use App\Tests\KernelServiceTestCase;
use Doctrine\ORM\Exception\ORMException;

class GroupMarginRepositoryTest extends KernelServiceTestCase
{
    use DatabaseTrait;
    use GroupTrait;

    private GroupMarginRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(GroupMarginRepository::class);
    }

    /**
     * @throws ORMException
     */
    public function testGetMargin(): void
    {
        $group = $this->getGroup();
        $actual = $this->repository->getMargin($group, 0.0);
        self::assertSame(1.1, $actual);
    }
}
