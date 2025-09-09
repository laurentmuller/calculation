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
use App\Traits\EntityTransformerTrait;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Data transformer to convert an entity to an identifier (integer).
 *
 * @template TEntity of EntityInterface
 *
 * @implements DataTransformerInterface<TEntity, int>
 */
readonly class EntityTransformer implements DataTransformerInterface
{
    /**
     * @use EntityTransformerTrait<TEntity>
     */
    use EntityTransformerTrait;

    /**
     * @phpstan-param AbstractRepository<TEntity> $repository
     */
    public function __construct(protected AbstractRepository $repository)
    {
        $this->className = $this->repository->getClassName();
    }

    /**
     * @param int|string|null $value
     *
     * @phpstan-return TEntity|null
     */
    #[\Override]
    public function reverseTransform(mixed $value): ?EntityInterface
    {
        return $this->toEntity($value);
    }

    /**
     * @phpstan-param EntityInterface|null $value
     */
    #[\Override]
    public function transform(mixed $value): ?int
    {
        return $this->toIdentifier($value);
    }
}
