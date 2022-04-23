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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Represents an application property.
 *
 * @ORM\Table(name="sy_Property", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="unique_property_name", columns={"name"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\PropertyRepository")
 * @UniqueEntity(fields="name", message="property.unique_name")
 */
class Property extends AbstractProperty
{
}
