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

namespace App\Entity;

use App\Repository\GlobalPropertyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Represents an application (global) property.
 */
#[ORM\Table(name: 'sy_Property')]
#[ORM\Entity(repositoryClass: GlobalPropertyRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_property_name', columns: ['name'])]
#[UniqueEntity(fields: 'name', message: 'property.unique_name')]
class GlobalProperty extends AbstractProperty
{
    /**
     * Create a new instance for the given.
     */
    public static function instance(string $name): self
    {
        return new self($name);
    }
}
