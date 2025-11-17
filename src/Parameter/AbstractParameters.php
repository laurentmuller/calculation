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
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Abstract parameters container.
 *
 * @template TProperty of AbstractProperty
 *
 * @phpstan-import-type TValue from Parameter
 *
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

    /**
     * @phpstan-return TProperty
     */
    abstract protected function createProperty(string $name): AbstractProperty;

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
        return $this->cache->get(
            $class::getCacheKey(),
            fn (): ParameterInterface => $this->createParameter($class, $default)
        );
    }

    /**
     * @phpstan-param TParameter ...$parameters
     *
     * @return array<string, array<string, mixed>>
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
                $values[$key][$metaData->property] = $this->getDefaultPropertyValue($metaData, $parameter, $accessor);
            }
            $values[$key] = \array_filter($values[$key], static fn (mixed $value): bool => null !== $value);
        }

        return \array_filter($values);
    }

    /**
     * @return AbstractRepository<TProperty>
     */
    abstract protected function getRepository(): AbstractRepository;

    /**
     * @phpstan-return TProperty[]
     */
    abstract protected function loadProperties(): array;

    /**
     * @param array<string, ?ParameterInterface> $parameters
     * @param array<string, ?ParameterInterface> $defaults
     */
    protected function saveParameters(array $parameters, array $defaults = []): bool
    {
        $saved = false;
        foreach ($parameters as $key => $parameter) {
            if (!$parameter instanceof ParameterInterface) {
                continue;
            }
            if ($this->saveParameter($parameter, $defaults[$key] ?? null)) {
                $saved = true;
            }
        }

        return $saved;
    }

    /**
     * @phpstan-param TParameter $parameter
     *
     * @return MetaData[]
     */
    private function createMetaDatas(ParameterInterface|string $parameter): array
    {
        $metaDatas = [];
        $properties = $this->getProperties($parameter);
        foreach ($properties as $property) {
            $attribute = $this->getAttribute($property);
            if (false === $attribute) {
                continue;
            }
            $metaDatas[] = new MetaData(
                $attribute->name,
                $property->name,
                \ltrim((string) $property->getType(), '?'),
                $attribute->default
            );
        }

        return $metaDatas;
    }

    /**
     * @phpstan-template T of ParameterInterface
     *
     * @phpstan-param class-string<T> $class
     * @phpstan-param T|null $default
     *
     * @phpstan-return T
     */
    private function createParameter(string $class, ?ParameterInterface $default = null): ParameterInterface
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

        return $parameter;
    }

    /**
     * @phpstan-param TProperty[]  $properties
     *
     * @phpstan-return TProperty|null
     */
    private function findProperty(array $properties, string $name): ?AbstractProperty
    {
        $filtered = \array_filter(
            $properties,
            static fn (AbstractProperty $property): bool => $name === $property->getName()
        );

        return [] !== $filtered ? \reset($filtered) : null;
    }

    private function getAccessor(): PropertyAccessor
    {
        return $this->accessor ??= PropertyAccess::createPropertyAccessor();
    }

    private function getAttribute(\ReflectionProperty $property): Parameter|false
    {
        /** @var \ReflectionAttribute<Parameter>[] $attributes */
        $attributes = $property->getAttributes(Parameter::class);

        return [] === $attributes ? false : $attributes[0]->newInstance();
    }

    /**
     * @param class-string<\BackedEnum> $type
     */
    private function getBackedEnumInt(string $type, AbstractProperty $property): ?\BackedEnum
    {
        return $type::tryFrom($property->getInteger());
    }

    /**
     * @param class-string<\BackedEnum> $type
     */
    private function getBackedEnumString(string $type, AbstractProperty $property): ?\BackedEnum
    {
        return $type::tryFrom((string) $property->getValue());
    }

    /**
     * @phpstan-return TValue
     */
    private function getDefaultPropertyValue(
        MetaData $metaData,
        ParameterInterface|string|null $parameter,
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
    private function getMetaDataDefaultValue(MetaData $metaData): mixed
    {
        return $metaData->default;
    }

    /**
     * @phpstan-param TParameter $parameter
     *
     * @return MetaData[]
     */
    private function getMetaDatas(ParameterInterface|string $parameter): array
    {
        $key = 'meta_data_' . $parameter::getCacheKey();

        return $this->cache->get($key, fn (): array => $this->createMetaDatas($parameter));
    }

    /**
     * @phpstan-return TValue
     */
    private function getParameterPropertyValue(
        MetaData $metaData,
        ParameterInterface $parameter,
        PropertyAccessor $accessor
    ): mixed {
        return $accessor->getValue($parameter, $metaData->property);
    }

    /**
     * @phpstan-param TParameter $parameter
     *
     * @return \ReflectionProperty[]
     */
    private function getProperties(ParameterInterface|string $parameter): array
    {
        return (new \ReflectionClass($parameter))
            ->getProperties(\ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED);
    }

    /**
     * @phpstan-return TValue
     */
    private function getPropertyValue(MetaData $metaData, AbstractProperty $property): mixed
    {
        return match (true) {
            'array' === $metaData->type => $property->getArray(),
            'bool' === $metaData->type => $property->getBoolean(),
            'float' === $metaData->type => $property->getFloat(),
            'int' === $metaData->type => $property->getInteger(),
            'string' === $metaData->type => $property->getValue(),
            DatePoint::class === $metaData->type => $property->getDate(),
            $metaData->isEnumTypeInt() => $this->getBackedEnumInt($metaData->type, $property),
            $metaData->isEnumTypeString() => $this->getBackedEnumString($metaData->type, $property),
            default => throw new \LogicException(\sprintf('Unsupported type "%s" for property "%s".', $metaData->type, $metaData->property))
        };
    }

    private function isDefaultValue(ParameterInterface $parameter, string $name, mixed $value): bool
    {
        $class = new \ReflectionClass($parameter);
        if (!$class->hasProperty($name)) {
            return false;
        }

        $property = $class->getProperty($name);
        $attribute = $this->getAttribute($property);

        return $attribute instanceof Parameter && $attribute->default === $value;
    }

    private function saveParameter(ParameterInterface $parameter, ?ParameterInterface $default = null): bool
    {
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
}
