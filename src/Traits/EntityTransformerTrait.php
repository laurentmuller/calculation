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

namespace App\Traits;

use App\Interfaces\EntityInterface;
use Doctrine\ORM\Proxy\DefaultProxyClassNameResolver;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Trait to convert an entity to or from an integer.
 *
 * @template TEntity of EntityInterface
 */
trait EntityTransformerTrait
{
    /**
     * @param int|string|null $value
     *
     * @return TEntity|null
     */
    protected function toEntity(mixed $value): ?EntityInterface
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!\is_numeric($value)) {
            throw new UnexpectedTypeException($value, 'numeric');
        }

        $entity = $this->repository->find((int) $value);
        if (null === $entity || !$this->validate($entity)) {
            throw new InvalidArgumentException(\sprintf('Unable to find a "%s" for the value "%s".', $this->getClassName(), $value));
        }

        return $entity;
    }

    /**
     * @param EntityInterface|null $value
     */
    protected function toIdentifier(mixed $value): ?int
    {
        if (null === $value) {
            return null;
        }

        if (!$this->validate($value)) {
            throw new UnexpectedTypeException($value, $this->getClassName());
        }

        return $value->getId();
    }

    /**
     * @return class-string<TEntity>
     */
    private function getClassName(): string
    {
        return $this->repository->getClassName();
    }

    private function validate(mixed $entity): bool
    {
        return \is_object($entity) && $this->getClassName() === DefaultProxyClassNameResolver::getClass($entity);
    }
}
