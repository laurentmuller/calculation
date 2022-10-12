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
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Trait to manage application and user properties.
 *
 * @see \App\Interfaces\PropertyServiceInterface
 */
trait PropertyTrait
{
    use CacheAwareTrait {
        clearCache as traitClearCache;
        saveDeferredCacheValue as traitSaveDeferredCacheValue;
    }
    use LoggerAwareTrait;
    use ServiceSubscriberTrait {
        setContainer as traitSetContainer;
    }
    use TranslatorAwareTrait;

    /**
     * Clear this cache.
     */
    public function clearCache(): bool
    {
        if (!$this->traitClearCache()) {
            $this->logWarning($this->trans('application_service.clear_error'));

            return false;
        }

        return true;
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
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getPropertyArray(string $name, array $default): array
    {
        $value = $this->getPropertyString($name);
        if (!\is_string($value)) {
            return $default;
        }

        /** @psalm-var mixed $array */
        $array = \json_decode($value, true);
        if (\JSON_ERROR_NONE !== \json_last_error() || !\is_array($array) || \count($array) !== \count($default)) {
            return $default;
        }

        return $array;
    }

    /**
     * Gets a date property.
     *
     * @param string              $name    the property name to search for
     * @param ?\DateTimeInterface $default the default value if the property is not found
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getPropertyDate(string $name, ?\DateTimeInterface $default = null): ?\DateTimeInterface
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
     * Gets a float property.
     *
     * @param string $name    the property name to search for
     * @param float  $default the default value if the property is not found
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getPropertyFloat(string $name, float $default = 0.0): float
    {
        return (float) $this->getItemValue($name, $default);
    }

    /**
     * Gets an integer property.
     *
     * @param string $name    the property name to search for
     * @param int    $default the default value if the property is not found
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getPropertyInteger(string $name, int $default = 0): int
    {
        return (int) $this->getItemValue($name, $default);
    }

    /**
     * Gets a string property.
     *
     * @param string  $name    the property name to search for
     * @param ?string $default the default value if the property is not found
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getPropertyString(string $name, ?string $default = null): ?string
    {
        /** @psalm-var mixed $value */
        $value = $this->getItemValue($name, $default);

        return \is_string($value) ? $value : $default;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isActionEdit(): bool
    {
        return EntityAction::EDIT === $this->getEditAction();
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isActionNone(): bool
    {
        return EntityAction::NONE === $this->getEditAction();
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isActionShow(): bool
    {
        return EntityAction::SHOW === $this->getEditAction();
    }

    /**
     * Gets a boolean property.
     *
     * @param string $name    the property name to search for
     * @param bool   $default the default value if the property is not found
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPropertyBoolean(string $name, bool $default = false): bool
    {
        return (bool) $this->getItemValue($name, $default);
    }

    public function saveDeferredCacheValue(string $key, mixed $value, int|\DateInterval|null $time = null): bool
    {
        if (!$this->traitSaveDeferredCacheValue($this->cleanKey($key), $value, $time)) {
            $this->logWarning($this->trans('application_service.deferred_error', ['%key%' => $key]));

            return false;
        }

        return true;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->traitSetContainer($container);
        $this->updateAdapter();
    }

    /**
     * Sets a single property value.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function setProperty(string $name, mixed $value): self
    {
        return $this->setProperties([$name => $value]);
    }

    /**
     * Update the cache if needed.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function updateCache(): void
    {
        if (!$this->getCacheValue(self::P_CACHE_SAVED, false)) {
            $this->updateAdapter();
        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    abstract protected function updateAdapter(): void;

    /**
     * Gets an item value.
     *
     * @param string $name    the item name
     * @param mixed  $default the default value if the item is not found
     *
     * @return mixed the value, if hit; the default value otherwise
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function getItemValue(string $name, mixed $default): mixed
    {
        return $this->getCacheValue($name, $default);
    }

    /**
     * Returns if the given value is the default value.
     *
     * @param array  $defaultProperties the default properties to get default value from
     * @param string $name              the property name
     * @param mixed  $value             the value to compare to
     *
     * @return bool true if default
     */
    private function isDefaultValue(array $defaultProperties, string $name, mixed $value): bool
    {
        return \array_key_exists($name, $defaultProperties) && $defaultProperties[$name] === $value;
    }

    /**
     * Load the properties.
     *
     * @return array<string, mixed>
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function loadProperties(): array
    {
        $this->updateAdapter();

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
            self::P_PANEL_CALCULATION => $this->getPanelCalculation(),
            self::P_PANEL_STATE => $this->isPanelState(),
            self::P_PANEL_MONTH => $this->isPanelMonth(),
            self::P_PANEL_CATALOG => $this->isPanelCatalog(),
            self::P_STATUS_BAR => $this->isStatusBar(),
            // document options
            self::P_QR_CODE => $this->isQrCode(),
            self::P_PRINT_ADDRESS => $this->isPrintAddress(),
        ];
    }

    /**
     * @param AbstractProperty[]|list<AbstractProperty> $properties
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function saveProperties(array $properties): void
    {
        $this->clearCache();
        foreach ($properties as $property) {
            $this->saveDeferredCacheValue($property->getName(), $property->getString());
        }
        $this->saveDeferredCacheValue(self::P_CACHE_SAVED, true);
        if (!$this->commitDeferredValues()) {
            $this->logWarning($this->trans('application_service.commit_error'));
        }
    }
}
