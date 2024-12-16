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

use App\Entity\AbstractProperty;
use App\Enums\EntityAction;
use App\Utils\StringUtils;

/**
 * Trait for class implementing <code>PropertyServiceInterface</code>.
 *
 * @psalm-require-implements \App\Interfaces\PropertyServiceInterface
 *
 * @template TProperty of AbstractProperty
 */
trait PropertyServiceTrait
{
    use CacheTrait {
        getCacheValue as private getCacheValueFromTrait;
    }

    // The saved cache state property name
    private const P_CACHE_SAVED = 'cache_saved';

    public function getCacheValue(string $key, mixed $default = null): mixed
    {
        $this->initialize();

        return $this->getCacheValueFromTrait($key, $default);
    }

    public function isActionEdit(): bool
    {
        return EntityAction::EDIT === $this->getEditAction();
    }

    public function isActionNone(): bool
    {
        return EntityAction::NONE === $this->getEditAction();
    }

    public function isActionShow(): bool
    {
        return EntityAction::SHOW === $this->getEditAction();
    }

    /**
     * Return a value indicating whether an actual connection to the database is established.
     */
    abstract public function isConnected(): bool;

    public function isDarkNavigation(): bool
    {
        return $this->getPropertyBoolean(self::P_DARK_NAVIGATION, true);
    }

    /**
     * Save the given properties to the database and to the cache.
     *
     * @param array<string, mixed> $properties the properties to set
     */
    abstract public function setProperties(array $properties): bool;

    /**
     * Sets a single property value.
     *
     * @return bool true if the property has changed
     */
    public function setProperty(string $name, mixed $value): bool
    {
        return $this->setProperties([$name => $value]);
    }

    /**
     * Gets an array property.
     *
     * @template T
     *
     * @param string $name    the property name to search for
     * @param T[]    $default the default array if the property is not found or is not valid
     *
     * @return T[]
     */
    protected function getPropertyArray(string $name, array $default): array
    {
        $value = $this->getPropertyString($name);
        if (!\is_string($value)) {
            return $default;
        }

        try {
            $result = StringUtils::decodeJson($value);
            if (\count($result) === \count($default)) {
                return $result;
            }
        } catch (\InvalidArgumentException) {
        }

        return $default;
    }

    /**
     * Gets a boolean property.
     *
     * @param string $name    the property name to search for
     * @param bool   $default the default value if the property is not found
     */
    protected function getPropertyBoolean(string $name, bool $default = false): bool
    {
        return (bool) $this->getCacheValue($name, $default);
    }

    /**
     * Gets a date property.
     *
     * @param string              $name    the property name to search for
     * @param ?\DateTimeInterface $default the default value if the property is not found
     *
     * @psalm-return ($default is null ? (\DateTimeInterface|null) : \DateTimeInterface)
     */
    protected function getPropertyDate(string $name, ?\DateTimeInterface $default = null): ?\DateTimeInterface
    {
        $timestamp = $this->getPropertyInteger($name);
        if (AbstractProperty::FALSE_VALUE !== $timestamp) {
            $date = \DateTime::createFromFormat('U', (string) $timestamp);
            if ($date instanceof \DateTime) {
                return $date;
            }
        }

        return $default;
    }

    /**
     * Gets an enumeration value.
     *
     * @template T of \BackedEnum
     *
     * @psalm-param T $default
     *
     * @psalm-return T
     */
    protected function getPropertyEnum(string $propertyName, \BackedEnum $default): \BackedEnum
    {
        $defaultValue = $default->value;
        if (\is_int($defaultValue)) {
            $value = $this->getPropertyInteger($propertyName, $defaultValue);
        } else {
            $value = $this->getPropertyString($propertyName, $defaultValue);
        }

        return $default::tryFrom($value) ?? $default;
    }

    /**
     * Gets a float property.
     *
     * @param string $name    the property name to search for
     * @param float  $default the default value if the property is not found
     */
    protected function getPropertyFloat(string $name, float $default = 0.0): float
    {
        return (float) $this->getCacheValue($name, $default);
    }

    /**
     * Gets an integer property.
     *
     * @param string $name    the property name to search for
     * @param int    $default the default value if the property is not found
     */
    protected function getPropertyInteger(string $name, int $default = 0): int
    {
        return (int) $this->getCacheValue($name, $default);
    }

    /**
     * Gets a string property.
     *
     * @param string  $name    the property name to search for
     * @param ?string $default the default value if the property is not found
     *
     * @psalm-return ($default is null ? (string|null) : string)
     */
    protected function getPropertyString(string $name, ?string $default = null): ?string
    {
        /** @psalm-var string|null $value */
        $value = $this->getCacheValue($name, $default);

        return \is_string($value) ? $value : $default;
    }

    /**
     * Initialize cached properties.
     */
    protected function initialize(): void
    {
        if (!(bool) $this->getCacheValueFromTrait(self::P_CACHE_SAVED, false) && $this->isConnected()) {
            $this->updateAdapter();
        }
    }

    /**
     * Returns if the given value is the default value.
     *
     * @param array<string, mixed> $defaultProperties the default properties to get the default value from
     * @param string               $name              the property name
     * @param mixed                $value             the value to compare to
     *
     * @return bool true if default
     */
    protected function isDefaultValue(array $defaultProperties, string $name, mixed $value): bool
    {
        return \array_key_exists($name, $defaultProperties) && $defaultProperties[$name] === $value;
    }

    /**
     * @param array<string, mixed> $properties
     */
    protected function isPropertiesChanged(array $properties): bool
    {
        $existing = $this->getProperties(false);
        /** @psalm-var mixed $value */
        foreach ($properties as $key => $value) {
            /** @psalm-var mixed $oldValue */
            $oldValue = $existing[$key] ?? null;
            if ($value !== $oldValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load entities from the database.
     *
     * @psalm-return TProperty[]
     */
    abstract protected function loadEntities(): array;

    /**
     * Load the properties.
     *
     * @return array<string, mixed>
     */
    protected function loadProperties(bool $updateAdapter = true): array
    {
        if ($updateAdapter) {
            $this->updateAdapter();
        }

        return [
            // display and edit entities
            self::P_DISPLAY_MODE => $this->getDisplayMode(),
            self::P_EDIT_ACTION => $this->getEditAction(),
            // notification
            self::P_MESSAGE_ICON => $this->isMessageIcon(),
            self::P_MESSAGE_TITLE => $this->isMessageTitle(),
            self::P_MESSAGE_SUB_TITLE => $this->isMessageSubTitle(),
            self::P_MESSAGE_CLOSE => $this->isMessageClose(),
            self::P_MESSAGE_PROGRESS => $this->getMessageProgress(),
            self::P_MESSAGE_POSITION => $this->getMessagePosition(),
            self::P_MESSAGE_TIMEOUT => $this->getMessageTimeout(),
            // home page
            self::P_CALCULATIONS => $this->getCalculations(),
            self::P_PANEL_STATE => $this->isPanelState(),
            self::P_PANEL_MONTH => $this->isPanelMonth(),
            self::P_PANEL_CATALOG => $this->isPanelCatalog(),
            self::P_STATUS_BAR => $this->isStatusBar(),
            self::P_DARK_NAVIGATION => $this->isDarkNavigation(),
            // document's options
            self::P_QR_CODE => $this->isQrCode(),
            self::P_PRINT_ADDRESS => $this->isPrintAddress(),
        ];
    }

    /**
     * Load entities from the database and save to the cache.
     */
    protected function updateAdapter(): void
    {
        $this->clearCache();
        $properties = $this->loadEntities();
        foreach ($properties as $property) {
            $this->saveDeferredCacheValue($property->getName(), $property->getValue());
        }
        $this->saveDeferredCacheValue(self::P_CACHE_SAVED, true);
        $this->commitDeferredValues();
    }
}
