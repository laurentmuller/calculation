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
 * @psalm-type TParameter = ParameterInterface|class-string<ParameterInterface>
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
     * Gets all default values.
     *
     * @return array<string, mixed>
     *
     * @psalm-return array<string, TValue>
     */
    abstract public function getDefaultValues(): array;

    /**
     * Save parameters.
     *
     * @return bool true if one of the parameters has changed
     */
    abstract public function save(): bool;

    protected function createMetaData(Parameter $attribute, \ReflectionProperty $property): MetaData
    {
        return new MetaData(
            $attribute->name,
            $property->name,
            \ltrim((string) $property->getType(), '?'),
            $attribute->default
        );
    }

    /**
     * @psalm-template T of ParameterInterface
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
    protected function getBackedEnumInt(string $type, AbstractProperty $property): ?\BackedEnum
    {
        return $type::tryFrom($property->getInteger());
    }

    /**
     * @param class-string<\BackedEnum> $type
     */
    protected function getBackedEnumString(string $type, AbstractProperty $property): ?\BackedEnum
    {
        return $type::tryFrom((string) $property->getValue());
    }

    /**
     * @psalm-template T of ParameterInterface
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
            return $this->getParameterPropertyValue($metaData, $parameter, $accessor);
        }

        return $this->getMetaDataDefaultValue($metaData);
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
     * @psalm-return TValue
     */
    protected function getMetaDataDefaultValue(MetaData $metaData): mixed
    {
        /** @psalm-var TValue */
        return $metaData->default;
    }

    /**
     * @return MetaData[]
     *
     * @psalm-param TParameter $parameter
     */
    protected function getMetaDatas(ParameterInterface|string $parameter): array
    {
        $key = 'meta_data_' . $parameter::getCacheKey();

        return $this->cache->get($key, function () use ($parameter): array {
            $metaDatas = [];
            $properties = $this->getProperties($parameter);
            foreach ($properties as $property) {
                $attributes = $property->getAttributes(Parameter::class);
                if ([] === $attributes) {
                    continue;
                }
                $attribute = $attributes[0]->newInstance();
                $metaDatas[] = $this->createMetaData($attribute, $property);
            }

            return $metaDatas;
        });
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
     * @psalm-param TParameter ...$parameters
     *
     * @psalm-return array<string, TValue>
     */
    protected function getParametersDefaultValues(ParameterInterface|string ...$parameters): array
    {
        $values = [];
        $accessor = $this->getAccessor();
        foreach ($parameters as $parameter) {
            $metaDatas = $this->getMetaDatas($parameter);
            foreach ($metaDatas as $metaData) {
                if ($parameter instanceof ParameterInterface) {
                    $values[$metaData->property] = $this->getParameterPropertyValue($metaData, $parameter, $accessor);
                } else {
                    $values[$metaData->property] = $this->getMetaDataDefaultValue($metaData);
                }
            }
        }

        return \array_filter($values);
    }

    /**
     * @return \ReflectionProperty[]
     *
     * @psalm-param TParameter $parameter
     */
    protected function getProperties(ParameterInterface|string $parameter): array
    {
        try {
            return (new \ReflectionClass($parameter))->getProperties(\ReflectionProperty::IS_PRIVATE);
        } catch (\ReflectionException $e) {
            $type = \is_string($parameter) ? $parameter : \get_debug_type($parameter);
            throw new \LogicException(\sprintf('Unable to get properties for "%s".', $type), $e->getCode(), $e);
        }
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
            $metaData->isEnumTypeInt() => $this->getBackedEnumInt($metaData->type, $property),
            $metaData->isEnumTypeString() => $this->getBackedEnumString($metaData->type, $property),
            $metaData->isEntityInterfaceType() => $this->getEntity($metaData->type, $property),
            default => throw new \LogicException(\sprintf('Unsupported type "%s" for property "%s".', $metaData->type, $metaData->property))
        };
    }

    /**
     * @return AbstractRepository<TProperty>
     */
    abstract protected function getRepository(): AbstractRepository;

    protected function isDefaultValue(ParameterInterface $parameter, string $name, mixed $value): bool
    {
        $class = new \ReflectionClass($parameter);
        if (!$class->hasProperty($name)) {
            return false;
        }

        $property = $class->getProperty($name);
        $attributes = $property->getAttributes(Parameter::class);
        if ([] === $attributes) {
            return false;
        }

        return $attributes[0]->newInstance()->default === $value;
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

        foreach ($metaDatas as $metaData) {
            $property = $this->findProperty($metaData->name);
            $value = $this->getParameterPropertyValue($metaData, $parameter, $accessor);
            $defaultValue = $this->getDefaultPropertyValue($metaData, $default, $accessor);
            if ($value === $defaultValue || $this->isDefaultValue($parameter, $metaData->property, $value)) {
                if ($property instanceof AbstractProperty) {
                    $repository->remove($property, false);
                    $changed = true;
                }
                continue;
            }

            if (!$property instanceof AbstractProperty) {
                $property = $this->createProperty($metaData->name);
            }
            $oldValue = $property->getValue();
            $property->setValue($value);
            if ($oldValue !== $property->getValue()) {
                $repository->persist($property, false);
                $changed = true;
            }
        }

        if ($changed) {
            $repository->flush();
            $this->cache->delete($parameter::getCacheKey());
        }

        return $changed;
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