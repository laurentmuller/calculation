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

namespace App\Parameter;

use App\Attribute\Parameter;
use App\Entity\AbstractProperty;
use App\Interfaces\EntityInterface;
use App\Repository\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Abstract parameters container.
 *
 * @template TProperty of AbstractProperty
 *
 * @psalm-type TValue = scalar|array|\BackedEnum|\DateTimeInterface|EntityInterface|null
 */
abstract class AbstractParameters
{
    private ?PropertyAccessor $accessor = null;

    public function __construct(
        protected readonly CacheInterface $cache,
        protected readonly EntityManagerInterface $manager,
    ) {
    }

    /**
     * @template T of ParameterInterface
     *
     * @psalm-param class-string<T> $class
     * @psalm-param T|null $default
     *
     * @psalm-return T
     */
    protected function createParameter(string $class, ?ParameterInterface $default = null): ParameterInterface
    {
        $parameter = new $class();
        $accessor = $this->getAccessor();
        $metaDatas = $this->getMetaDatas($parameter);

        foreach ($metaDatas as $metaData) {
            $property = $this->findProperty($metaData->name);
            if ($property instanceof AbstractProperty) {
                $value = $this->getPropertyValue($metaData, $property);
            } else {
                $value = $this->getDefaultPropertyValue($metaData, $default, $accessor);
            }
            if (null !== $value) {
                $accessor->setValue($parameter, $metaData->property, $value);
            }
        }

        /** @phpstan-var T */
        return $parameter;
    }

    /**
     * @psalm-return TProperty
     */
    abstract protected function createProperty(string $name): AbstractProperty;

    /**
     * @psalm-return TProperty|null
     */
    abstract protected function findProperty(string $name): ?AbstractProperty;

    protected function getAccessor(): PropertyAccessor
    {
        return $this->accessor ??= PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param class-string<\BackedEnum> $type
     */
    protected function getBackEnum(string $type, AbstractProperty $property): ?\BackedEnum
    {
        if ($this->isStringEnum($type)) {
            return $type::tryFrom((string) $property->getValue());
        }

        return $type::tryFrom($property->getInteger());
    }

    /**
     * @template T of ParameterInterface
     *
     * @psalm-param class-string<T> $class
     * @psalm-param T|null $default
     *
     * @psalm-return T
     */
    protected function getCachedParameter(string $class, ?ParameterInterface $default = null): ParameterInterface
    {
        return $this->cache->get(
            $class::getCacheKey(),
            fn (): ParameterInterface => $this->createParameter($class, $default)
        );
    }

    /**
     * @psalm-return TValue
     */
    protected function getDefaultPropertyValue(
        MetaData $metaData,
        ?ParameterInterface $parameter,
        PropertyAccessor $accessor
    ): mixed {
        if ($parameter instanceof ParameterInterface) {
            /** @psalm-var TValue */
            return $accessor->getValue($parameter, $metaData->property);
        }

        /** @psalm-var TValue */
        return $metaData->default;
    }

    /**
     * @psalm-param class-string<EntityInterface> $type
     */
    protected function getEntity(string $type, AbstractProperty $property): ?EntityInterface
    {
        return $this->manager->getRepository($type)
            ->find($property->getInteger());
    }

    /**
     * @psalm-return MetaData[]
     */
    protected function getMetaDatas(ParameterInterface $parameter): array
    {
        $metaDatas = [];
        $properties = $this->getProperties($parameter);
        foreach ($properties as $property) {
            $attributes = $property->getAttributes(Parameter::class);
            if ([] === $attributes) {
                continue;
            }

            /** @psalm-var  \ReflectionNamedType $type */
            $type = $property->getType();
            $attribute = $attributes[0]->newInstance();

            $metaDatas[] = new MetaData(
                $attribute->name,
                $property->name,
                \ltrim((string) $type, '?'),
                $attribute->default
            );
        }

        return $metaDatas;
    }

    /**
     * @psalm-return TValue
     */
    protected function getParameterPropertyValue(
        MetaData $metaData,
        ParameterInterface $parameter,
        PropertyAccessor $accessor
    ): mixed {
        /** @psalm-var TValue */
        return $accessor->getValue($parameter, $metaData->property);
    }

    /**
     * @return \ReflectionProperty[]
     */
    protected function getProperties(ParameterInterface $parameter): array
    {
        $class = new \ReflectionClass($parameter);

        return $class->getProperties(\ReflectionProperty::IS_PRIVATE);
    }

    /**
     * @psalm-return TValue
     */
    protected function getPropertyValue(MetaData $metaData, AbstractProperty $property): mixed
    {
        return match (true) {
            'array' === $metaData->type => $property->getArray(),
            'bool' === $metaData->type => $property->getBoolean(),
            'float' === $metaData->type => $property->getFloat(),
            'int' === $metaData->type => $property->getInteger(),
            'string' === $metaData->type => $property->getValue(),
            'DateTimeInterface' === $metaData->type => $property->getDate(),
            $metaData->isBackedEnumType() => $this->getBackEnum($metaData->type, $property),
            $metaData->isEntityInterfaceType() => $this->getEntity($metaData->type, $property),
            default => throw new \LogicException(\sprintf('Unsupported type "%s" for property "%s".', $metaData->type, $metaData->property))
        };
    }

    /**
     * @return AbstractRepository<TProperty>
     */
    abstract protected function getRepository(): AbstractRepository;

    /**
     * @param class-string<\BackedEnum> $type
     */
    protected function isStringEnum(string $type): bool
    {
        try {
            $enum = new \ReflectionEnum($type);

            return 'string' === (string) $enum->getBackingType();
        } catch (\ReflectionException $e) {
            throw new \LogicException(\sprintf('Unable to get enum for type "%s".', $type), $e->getCode(), $e);
        }
    }

    protected function saveParameter(?ParameterInterface $parameter, ?ParameterInterface $default = null): bool
    {
        if (!$parameter instanceof ParameterInterface) {
            return false;
        }

        $changed = false;
        $accessor = $this->getAccessor();
        $repository = $this->getRepository();
        $metaDatas = $this->getMetaDatas($parameter);

        try {
            foreach ($metaDatas as $metaData) {
                $property = $this->findProperty($metaData->name);
                $value = $this->getParameterPropertyValue($metaData, $parameter, $accessor);
                $defaultValue = $this->getDefaultPropertyValue($metaData, $default, $accessor);
                if ($value === $defaultValue || Parameter::isDefaultValue($parameter, $metaData->property, $value)) {
                    if ($property instanceof AbstractProperty) {
                        $repository->remove($property, false);
                        $changed = true;
                    }
                    continue;
                }

                if (!$property instanceof AbstractProperty) {
                    $property = $this->createProperty($metaData->name);
                    $changed = true;
                }
                $property->setValue($value);
                $repository->persist($property, false);
            }

            if ($changed) {
                $repository->flush();
                $this->cache->delete($parameter::getCacheKey());
            }

            return $changed;
        } catch (\ReflectionException $e) {
            throw new \LogicException(\sprintf('Unable to save parameter "%s".', \get_debug_type($parameter)), $e->getCode(), $e);
        }
    }

    /**
     * @param array<?ParameterInterface> $parameters
     * @param array<?ParameterInterface> $defaults
     */
    protected function saveParameters(array $parameters, array $defaults = []): bool
    {
        $saved = false;
        for ($i = 0, $count = \count($parameters); $i < $count; ++$i) {
            if ($this->saveParameter($parameters[$i], $defaults[$i] ?? null)) {
                $saved = true;
            }
        }

        return $saved;
    }
}
