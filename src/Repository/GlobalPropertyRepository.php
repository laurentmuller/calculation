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

namespace App\Repository;

use App\Entity\GlobalProperty;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for global property entity.
 *
 * @template-extends AbstractRepository<GlobalProperty>
 */
class GlobalPropertyRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GlobalProperty::class);
    }

    /**
     * Gets a property for the given name.
     */
    public function findOneByName(string $name): ?GlobalProperty
    {
        return $this->findOneBy(['name' => $name]);
    }
}
