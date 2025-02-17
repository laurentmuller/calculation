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
 * Data transformer to convert entity to identifier.
 *
 * @template TEntity of EntityInterface
 *
 * @extends AbstractEntityTransformer<TEntity, TEntity, int>
 */
class EntityTransformer extends AbstractEntityTransformer
{
    /**
     * @psalm-param AbstractRepository<TEntity> $repository
     */
    public function __construct(AbstractRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @param int|string|null $value
     *
     * @psalm-return TEntity|null
     */
    #[\Override]
    public function reverseTransform(mixed $value): ?EntityInterface
    {
        return $this->toEntity($value);
    }

    /**
     * @psalm-param EntityInterface|null $value
     */
    #[\Override]
    public function transform(mixed $value): ?int
    {
        return $this->toIdentifier($value);
    }
}
