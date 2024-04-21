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

use App\Entity\AbstractEntity;

/**
 * Trait to set entity identifier.
 */
trait IdTrait
{
    /**
     * @template T of AbstractEntity
     *
     * @psalm-param T $entity
     *
     * @psalm-return  T
     *
     * @throws \ReflectionException
     */
    private function setId(object $entity, int $id = 1): object
    {
        $class = new \ReflectionClass($entity::class);
        $property = $class->getProperty('id');
        $property->setValue($entity, $id);

        return $entity;
    }
}
