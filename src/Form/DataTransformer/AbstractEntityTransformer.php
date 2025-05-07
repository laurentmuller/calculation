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
use Doctrine\ORM\Proxy\DefaultProxyClassNameResolver;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Abstract data transformer to convert entities and identifiers.
 *
 * @template TEntity of EntityInterface
 * @template TValue of TEntity|int
 * @template TTransformedValue of TEntity|int
 *
 * @template-implements DataTransformerInterface<TValue, TTransformedValue>
 */
abstract class AbstractEntityTransformer implements DataTransformerInterface
{
    /**
     * @var class-string<TEntity>
     */
    private readonly string $className;

    /**
     * @param AbstractRepository<TEntity> $repository
     */
    public function __construct(private readonly AbstractRepository $repository)
    {
        $this->className = $this->repository->getClassName();
    }

    /**
     * @param int|string|null $value
     *
     * @phpstan-return TEntity|null
     */
    protected function toEntity(mixed $value): ?EntityInterface
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!\is_numeric($value)) {
            throw new TransformationFailedException(\sprintf('A "numeric" value expected, a "%s" given.', \get_debug_type($value)));
        }

        $entity = $this->repository->find((int) $value);
        if (null === $entity || !$this->validate($entity)) {
            throw new TransformationFailedException(\sprintf('Unable to find a "%s" for the value "%s".', $this->className, $value));
        }

        return $entity;
    }

    /**
     * @phpstan-param EntityInterface|null $value
     */
    protected function toIdentifier(mixed $value): ?int
    {
        if (null === $value) {
            return null;
        }

        if (!$this->validate($value)) {
            throw new TransformationFailedException(\sprintf('A "%s" expected, a "%s" given.', $this->className, \get_debug_type($value)));
        }

        return $value->getId();
    }

    protected function validate(mixed $entity): bool
    {
        return \is_object($entity) && $this->className === DefaultProxyClassNameResolver::getClass($entity);
    }
}
