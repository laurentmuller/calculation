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
 * Trait to convert an entity to or from an identifier (integer).
 *
 * @template TEntity of EntityInterface
 */
trait EntityTransformerTrait
{
    /**
     * @var class-string
     */
    private readonly string $className;

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
            throw new UnexpectedTypeException($value, 'numeric');
        }

        $entity = $this->repository->find((int) $value);
        if (null === $entity || !$this->validate($entity)) {
            throw new InvalidArgumentException(\sprintf('Unable to find a "%s" for the value "%s".', $this->className, $value));
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
            throw new UnexpectedTypeException($value, $this->className);
        }

        return $value->getId();
    }

    private function validate(mixed $entity): bool
    {
        return \is_object($entity) && $this->className === DefaultProxyClassNameResolver::getClass($entity);
    }
}
