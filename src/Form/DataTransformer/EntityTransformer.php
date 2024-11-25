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
 * Data transformer to convert entity to identifier.
 *
 * @template TEntity of EntityInterface
 *
 * @template-implements DataTransformerInterface<TEntity, int>
 */
readonly class EntityTransformer implements DataTransformerInterface
{
    /**
     * @var class-string<TEntity>
     */
    private string $className;

    /**
     * @param AbstractRepository<TEntity> $repository
     */
    public function __construct(private AbstractRepository $repository)
    {
        $this->className = $this->repository->getClassName();
    }

    /**
     * @param int|string|null $value
     *
     * @psalm-return TEntity|null
     */
    public function reverseTransform(mixed $value): ?EntityInterface
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!\is_numeric($value)) {
            $message = \sprintf('A "numeric" value expected, a "%s" given.', \get_debug_type($value));
            throw new TransformationFailedException($message);
        }

        $entity = $this->repository->find((int) $value);
        if (null === $entity || !$this->validate($entity)) {
            $message = \sprintf('Unable to find a "%s" for the value "%s".', $this->className, $value);
            throw new TransformationFailedException($message);
        }

        return $entity;
    }

    /**
     * @psalm-param EntityInterface|null $value
     */
    public function transform(mixed $value): ?int
    {
        if (null === $value) {
            return null;
        }

        if (!$this->validate($value)) {
            $message = \sprintf('A "%s" expected, a "%s" given.', $this->className, \get_debug_type($value));
            throw new TransformationFailedException($message);
        }

        return $value->getId();
    }

    private function validate(mixed $entity): bool
    {
        if (!\is_object($entity)) {
            return false;
        }

        return $this->className === DefaultProxyClassNameResolver::getClass($entity);
    }
}
