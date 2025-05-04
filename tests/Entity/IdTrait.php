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

namespace App\Tests\Entity;

use App\Interfaces\EntityInterface;

/**
 * Trait to set entity identifier.
 */
trait IdTrait
{
    /**
     * @template T of EntityInterface
     *
     * @phpstan-param T $entity
     *
     * @phpstan-return  T
     *
     * @throws \ReflectionException
     */
    protected static function setId(EntityInterface $entity, int $id = 1): EntityInterface
    {
        $class = new \ReflectionClass($entity::class);
        $property = $class->getProperty('id');
        $property->setValue($entity, $id);

        return $entity;
    }
}
