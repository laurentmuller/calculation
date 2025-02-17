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

use App\Entity\GlobalProperty;
use App\Repository\GlobalPropertyRepository;
use App\Tests\DatabaseTrait;
use App\Tests\KernelServiceTestCase;

class GlobalPropertyRepositoryTest extends KernelServiceTestCase
{
    use DatabaseTrait;

    private GlobalPropertyRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(GlobalPropertyRepository::class);
    }

    public function testFindOneByName(): void
    {
        $actual = $this->repository->findOneByName('name');
        self::assertNull($actual);

        $property = new GlobalProperty();
        $property->setName('name')
            ->setValue('value');
        $this->repository->persist($property);

        $actual = $this->repository->findOneByName('fake');
        self::assertNull($actual);

        $actual = $this->repository->findOneByName('name');
        self::assertNotNull($actual);
    }
}
