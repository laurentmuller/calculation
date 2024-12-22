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

namespace App\Form\DataTransformer;

use App\Interfaces\EntityInterface;
use App\Repository\AbstractRepository;

/**
 * Data transformer to convert identifier to entity.
 *
 * @template TEntity of EntityInterface
 *
 * @extends AbstractEntityTransformer<TEntity, int, TEntity>
 */
class IdentifierTransformer extends AbstractEntityTransformer
{
    /**
     * @psalm-param AbstractRepository<TEntity> $repository
     */
    public function __construct(AbstractRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @psalm-param EntityInterface|null $value
     */
    public function reverseTransform(mixed $value): ?int
    {
        return $this->toIdentifier($value);
    }

    /**
     * @param int|string|null $value
     *
     * @psalm-return TEntity|null
     */
    public function transform(mixed $value): ?EntityInterface
    {
        return $this->toEntity($value);
    }
}
