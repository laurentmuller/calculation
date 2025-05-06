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
use App\Repository\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Abstract parameters container.
 *
 * @template TProperty of AbstractProperty
 *
 * @phpstan-type TValue = scalar|array|\BackedEnum|\DateTimeInterface|null
 * @phpstan-type TParameter = ParameterInterface|class-string<ParameterInterface>
 */
abstract class AbstractParameters
{
    #[Assert\Valid]
    protected ?DisplayParameter $display = null;

    #[Assert\Valid]
    protected ?HomePageParameter $homePage = null;

    #[Assert\Valid]
    protected ?MessageParameter $message = null;

    #[Assert\Valid]
    protected ?OptionsParameter $options = null;

    private ?PropertyAccessor $accessor = null;

    public function __construct(
        protected readonly CacheInterface $cache,
        protected readonly EntityManagerInterface $manager,
    ) {
    }

    /**
     * Gets all default values.
     *
     * @return array<string, array<string, mixed>>
     *
     * @phpstan-return array<array<string, TValue>>
     */
    abstract public function getDefaultValues(): array;

    /**
     * Gets the display parameter.
     */
    public function getDisplay(): DisplayParameter
    {
        return $this->display ??= $this->getCachedParameter(DisplayParameter::class);
    }

    /**
     * Gets the home page parameter.
     */
    public function getHomePage(): HomePageParameter
    {
        return $this->homePage ??= $this->getCachedParameter(HomePageParameter::class);
    }

    /**
     * Gets the message parameter.
     */
    public function getMessage(): MessageParameter
    {
        return $this->message ??= $this->getCachedParameter(MessageParameter::class);
    }

    /**
     * Gets the option parameter.
     */
    public function getOptions(): OptionsParameter
    {
        return $this->options ??= $this->getCachedParameter(OptionsParameter::class);
    }

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
     * @phpstan-template T of ParameterInterface
     *
     * @phpstan-param class-string<T> $class
     * @phpstan-param T|null $default
     *
     * @phpstan-return T
     */
    protected function createParameter(string $class, ?ParameterInterface $default = null): ParameterInterface
    {
        $parameter = new $class();
        $accessor = $this->getAccessor();
        $metaDatas = $this->getMetaDatas($parameter);
        $properties = $this->loadProperties();

        foreach ($metaDatas as $metaData) {
            $property = $this->findProperty($properties, $metaData->name);
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
     * @phpstan-return TProperty
     */
    abstract protected function createProperty(string $name): AbstractProperty;

    /**
     * @phpstan-param TProperty[]  $properties
     *
     * @phpstan-return TProperty|null
     */
    protected function findProperty(array $properties, string $name): ?AbstractProperty
    {
        foreach ($properties as $property) {
            if ($name === $property->getName()) {
                return $property;
            }
        }

        return null;
    }

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
     * @phpstan-template T of ParameterInterface
     *
     * @phpstan-param class-string<T> $class
     * @phpstan-param T|null $default
     *
     * @phpstan-return T
     */
    protected function getCachedParameter(string $class, ?ParameterInterface $default = null): ParameterInterface
    {
        /** @phpstan-var T */
        return $this->cache->get(
            $class::getCacheKey(),
            fn (): ParameterInterface => $this->createParameter($class, $default)
        );
    }

    /**
     * @phpstan-return TValue
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
     * @phpstan-return TValue
     */
    protected function getMetaDataDefaultValue(MetaData $metaData): mixed
    {
        /** @phpstan-var TValue */
        return $metaData->default;
    }

    /**
     * @return MetaData[]
     *
     * @phpstan-param TParameter $parameter
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
     * @phpstan-return TValue
     */
    protected function getParameterPropertyValue(
        MetaData $metaData,
        ParameterInterface $parameter,
        PropertyAccessor $accessor
    ): mixed {
        /** @phpstan-var TValue */
        return $accessor->getValue($parameter, $metaData->property);
    }

    /**
     * @return array<string, array<string, mixed>>
     *
     * @phpstan-param TParameter ...$parameters
     *
     * @phpstan-return array<string, array<string, TValue>>
     */
    protected function getParametersDefaultValues(ParameterInterface|string ...$parameters): array
    {
        $values = [];
        $accessor = $this->getAccessor();
        foreach ($parameters as $parameter) {
            $key = $parameter::getCacheKey();
            $metaDatas = $this->getMetaDatas($parameter);
            foreach ($metaDatas as $metaData) {
                if ($parameter instanceof ParameterInterface) {
                    $values[$key][$metaData->property] = $this->getParameterPropertyValue($metaData, $parameter, $accessor);
                } else {
                    $values[$key][$metaData->property] = $this->getMetaDataDefaultValue($metaData);
                }
            }
            $values[$key] = \array_filter($values[$key], static fn ($value): bool => null !== $value);
        }

        return \array_filter($values);
    }

    /**
     * @return \ReflectionProperty[]
     *
     * @phpstan-param TParameter $parameter
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
     * @phpstan-return TValue
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

    /**
     * @phpstan-return TProperty[]
     */
    abstract protected function loadProperties(): array;

    protected function saveParameter(?ParameterInterface $parameter, ?ParameterInterface $default = null): bool
    {
        if (!$parameter instanceof ParameterInterface) {
            return false;
        }

        $changed = false;
        $accessor = $this->getAccessor();
        $repository = $this->getRepository();
        $properties = $this->loadProperties();
        $metaDatas = $this->getMetaDatas($parameter);

        foreach ($metaDatas as $metaData) {
            $property = $this->findProperty($properties, $metaData->name);
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
