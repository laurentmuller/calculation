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

/**
 * @extends AbstractRepositoryTestCase<GlobalProperty, GlobalPropertyRepository>
 */
final class GlobalPropertyRepositoryTest extends AbstractRepositoryTestCase
{
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

    #[\Override]
    protected function getRepositoryClass(): string
    {
        return GlobalPropertyRepository::class;
    }
}
