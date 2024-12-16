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
 * @template TProperty of AbstractProperty
 */
abstract class AbstractParameterContainer
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
            $value = null;
            $property = $this->findProperty($metaData->name);
            if (!$property instanceof AbstractProperty) {
                /** @psalm-var mixed $value */
                $value = $this->getDefaultPropertyValue($accessor, $default, $metaData);
                if (null !== $value) {
                    $accessor->setValue($parameter, $metaData->property, $value);
                }
                continue;
            }
            switch ($metaData->type) {
                case 'bool':
                    $value = $property->getBoolean();
                    break;
                case 'int':
                    $value = $property->getInteger();
                    break;
                case 'float':
                    $value = $property->getFloat();
                    break;
                case 'string':
                    $value = $property->getValue();
                    break;
                case 'array':
                    $value = $property->getArray();
                    break;
                case 'DateTimeInterface':
                    $value = $property->getDate();
                    break;
                default:
                    if ($metaData->isBackedEnumType()) {
                        $value = $this->getBackEnum($metaData->type, $property);
                        break;
                    }
                    if ($metaData->isEntityInterfaceType()) {
                        $value = $this->getEntity($metaData->type, $property->getInteger());
                        break;
                    }
                    throw new \LogicException(\sprintf('Unsupported type "%s" for property "%s".', $metaData->type, $metaData->property));
            }

            if (null !== $value) {
                $accessor->setValue($parameter, $metaData->property, $value);
            }
        }

        /** @psalm-var T */
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
        if ($this->IsStringEnum($type)) {
            return $type::tryFrom((string) $property->getValue());
        }

        return $type::tryFrom($property->getInteger());
    }

    /**
     * Gets the given parameter type from the cache.
     *
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

    protected function getDefaultPropertyValue(
        PropertyAccessor $accessor,
        ?ParameterInterface $parameter,
        MetaData $metaData
    ): mixed {
        if (!$parameter instanceof ParameterInterface) {
            return $metaData->default;
        }

        return $accessor->getValue($parameter, $metaData->property);
    }

    /**
     * @psalm-param class-string<EntityInterface> $type
     */
    protected function getEntity(string $type, int $value): ?EntityInterface
    {
        /** psalm-var ?EntityInterface */
        return $this->manager->getRepository($type)->find($value);
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
     * @return \ReflectionProperty[]
     */
    protected function getProperties(ParameterInterface $parameter): array
    {
        $class = new \ReflectionClass($parameter);

        return $class->getProperties(\ReflectionProperty::IS_PRIVATE);
    }

    /**
     * @psalm-return class-string<TProperty>
     */
    abstract protected function getPropertyClass(): string;

    /**
     * @param class-string<\BackedEnum> $type
     */
    protected function IsStringEnum(string $type): bool
    {
        try {
            $enum = new \ReflectionEnum($type);
            if (!$enum->isBacked()) {
                throw new \LogicException(\sprintf('Type "%s" is not a backed enum.', $type));
            }

            return 'string' === (string) $enum->getBackingType();
        } catch (\ReflectionException $e) {
            throw new \LogicException(\sprintf('Unable to get enum for type "%s".', $type), $e->getCode(), $e);
        }
    }

    /**
     * @template T of ParameterInterface
     *
     * @psalm-param T|null $parameter
     * @psalm-param T|null $default
     */
    protected function saveParameter(?ParameterInterface $parameter, ?ParameterInterface $default = null): bool
    {
        if (!$parameter instanceof ParameterInterface) {
            return false;
        }

        $changed = false;
        $accessor = $this->getAccessor();
        $metaDatas = $this->getMetaDatas($parameter);
        /** @psalm-var AbstractRepository<TProperty> $repository */
        $repository = $this->manager->getRepository($this->getPropertyClass());

        try {
            foreach ($metaDatas as $metaData) {
                $property = $this->findProperty($metaData->name);
                /** @psalm-var mixed $value */
                $value = $accessor->getValue($parameter, $metaData->property);
                /** @psalm-var mixed $defaultValue */
                $defaultValue = $this->getDefaultPropertyValue($accessor, $default, $metaData);
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
                //  $repository->flush();
                $this->cache->delete($parameter::getCacheKey());
            }

            return $changed;
        } catch (\ReflectionException $e) {
            throw new \LogicException(\sprintf('Unable to save parameter "%s".', \get_debug_type($parameter)), $e->getCode(), $e);
        }
    }
}
