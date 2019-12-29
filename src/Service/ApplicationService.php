<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Service;

use App\Entity\CalculationState;
use App\Entity\Property;
use App\Interfaces\IApplicationService;
use App\Repository\PropertyRepository;
use App\Traits\LoggerTrait;
use App\Utils\FormatUtils;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Service to manage application properties.
 *
 * @author Laurent Muller
 */
class ApplicationService implements IApplicationService
{
    use LoggerTrait;

    /**
     * The cache namespace.
     */
    private const CACHE_NAME_SPACE = 'ApplicationService';

    /**
     * The cache saved key.
     */
    private const CACHE_SAVED = 'cache_saved';

    /**
     * The cache timeout (60 minutes).
     */
    private const CACHE_TIMEOUT = 60 * 60;

    /**
     * The cache adapter.
     *
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * The debug mode.
     *
     * @var bool
     */
    private $debug;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $manager, LoggerInterface $logger, KernelInterface $kernel)
    {
        $this->manager = $manager;
        $this->logger = $logger;
        $this->debug = $kernel->isDebug();

        $dir = $kernel->getCacheDir();
        $this->adapter = AbstractAdapter::createSystemCache(self::CACHE_NAME_SPACE, self::CACHE_TIMEOUT, null, $dir, $logger);
    }

    /**
     * Clear this cache.
     *
     * @return true if the cache was successfully cleared; false if there was an error
     */
    public function clearCache(): bool
    {
        if ($this->adapter->clear()) {
            $this->logInfo('Cleared the properties cache successfully.', $this->getLogContext());

            return true;
        }
        $this->logWarning('Error while clearing properties cache.', $this->getLogContext());

        return false;
    }

    /**
     * Gets the administrator role rights.
     *
     * @return string|null the rights
     */
    public function getAdminRights(): ?string
    {
        return $this->getString(self::ADMIN_RIGHTS);
    }

    /**
     * Gets this cache class short name.
     *
     * @return string the class name
     */
    public function getCacheClass(): string
    {
        return  Utils::getShortName($this->adapter);
    }

    /**
     * Gets the customer name.
     *
     * @return string|null the customer name
     */
    public function getCustomerName(): ?string
    {
        return $this->getString(self::CUSTOMER_NAME);
    }

    /**
     * Gets the customer web site (URL).
     *
     * @return string|null the customer web site
     */
    public function getCustomerUrl(): ?string
    {
        return $this->getString(self::CUSTOMER_URL);
    }

    /**
     * Gets the date format.
     *
     * @return int one of the date formatter constants (SHORT, MEDIUM or LONG)
     */
    public function getDateFormat(): int
    {
        return $this->getInteger(self::DATE_FORMAT, FormatUtils::getDateType());
    }

    /**
     * Gets the decimal separator symbol.
     *
     * @return string the separator
     */
    public function getDecimal(): string
    {
        return $this->getString(self::DECIMAL_SEPARATOR, FormatUtils::getDecimal());
    }

    /**
     * Gets the default calculation state.
     *
     * @return CalculationState|null the calculation state, if any; null otherwise
     */
    public function getDefaultState(): ?CalculationState
    {
        $id = $this->getDefaultStateId();
        if (!empty($id)) {
            $repository = $this->manager->getRepository(CalculationState::class);

            return $repository->find($id);
        }

        return null;
    }

    /**
     * Gets the default calculation state identifier.
     *
     * @return int the calculation state identifer, if any; 0 otherwise
     */
    public function getDefaultStateId(): int
    {
        return $this->getInteger(self::DEFAULT_STATE);
    }

    /**
     * Gets the number grouping separator symbol.
     *
     * @return string the separator
     */
    public function getGrouping(): string
    {
        return $this->getString(self::GROUPING_SEPARATOR, FormatUtils::getGrouping());
    }

    /**
     * Gets the last import of Swiss cities.
     *
     * @return \DateTime|null the last import or NULL if none
     */
    public function getLastImport(): ?\DateTime
    {
        return $this->getDate(self::LAST_IMPORT);
    }

    /**
     * Gets the last calculations update.
     *
     * @return \DateTime|null the last update or NULL if none
     */
    public function getLastUpdate(): ?\DateTime
    {
        return $this->getDate(self::LAST_UPDATE);
    }

    /**
     * Gets the position of the flashbag messages (default: 'bottom-right').
     *
     * @return string the position
     */
    public function getMessagePosition(): string
    {
        return $this->getString(self::MESSAGE_POSITION, self::DEFAULT_POSITION);
    }

    /**
     * Gets the timeout, in milliseconds, of the flashbag messages (default: 4000 ms).
     *
     * @return int the timeout
     */
    public function getMessageTimeout(): int
    {
        return $this->getInteger(self::MESSAGE_TIMEOUT, self::DEFAULT_TIMEOUT);
    }

    /**
     * Gets the minimum margin, in percent, for a calculation.
     */
    public function getMinMargin(): float
    {
        return $this->getFloat(self::MIN_MARGIN, self::DEFAULT_MIN_MARGIN);
    }

    /**
     * Gets all properties.
     *
     * @return array the properties with names and values
     */
    public function getProperties(): array
    {
        // reload data
        $this->updateAdapter();

        return  [
            self::CUSTOMER_NAME => $this->getCustomerName(),
            self::CUSTOMER_URL => $this->getCustomerUrl(),

            self::EDIT_ACTION => $this->isEditAction(),
            self::DEFAULT_STATE => $this->getDefaultState(),

            self::MESSAGE_POSITION => $this->getMessagePosition(),
            self::MESSAGE_TIMEOUT => $this->getMessageTimeout(),
            self::MESSAGE_SUB_TITLE => $this->isMessageSubTitle(),

            self::DATE_FORMAT => $this->getDateFormat(),
            self::TIME_FORMAT => $this->getTimeFormat(),

            self::GROUPING_SEPARATOR => $this->getGrouping(),
            self::DECIMAL_SEPARATOR => $this->getDecimal(),

            self::LAST_UPDATE => $this->getLastUpdate(),
            self::LAST_IMPORT => $this->getLastImport(),

            self::MIN_MARGIN => $this->getMinMargin(),

            self::DISPLAY_CAPTCHA => $this->isDisplayCaptcha(),
        ];
    }

    /**
     * Gets the time format.
     *
     * @return int one of the date formatter constants (SHORT or MEDIUM)
     */
    public function getTimeFormat(): int
    {
        return $this->getInteger(self::TIME_FORMAT, FormatUtils::getTimeType());
    }

    /**
     * Gets the user role rights.
     *
     * @return string|null the rights
     */
    public function getUserRights(): ?string
    {
        return $this->getString(self::USER_RIGHTS);
    }

    /**
     * Returns if the debug mode is enabled.
     *
     * @return bool true if the debug mode is enabled, false otherwise
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * Gets a value indicating the image captcha is displayed when login.
     *
     * @return bool <code>true</code> to display the image; <code>false</code> to hide
     */
    public function isDisplayCaptcha(): bool
    {
        return $this->getBoolean(self::DISPLAY_CAPTCHA, !$this->debug);
    }

    /**
     * Gets the default action.
     *
     * @return bool <code>false</code> to display the entity properties; <code>true</code> to edit the entity
     */
    public function isEditAction(): bool
    {
        return $this->getBoolean(self::EDIT_ACTION, self::DEFAULT_EDIT_ACTION);
    }

    /**
     * Returns if the given margin is below the minimum.
     *
     * @param float $margin the overall margin to be tested
     *
     * @return bool true if below
     */
    public function isMarginBelow(float $margin): bool
    {
        return $margin < $this->getMinMargin();
    }

    /**
     * Returns if the flashbag message sub-title is displayed (default: true).
     *
     * @return bool true if displayed
     */
    public function isMessageSubTitle(): bool
    {
        return $this->getBoolean(self::MESSAGE_SUB_TITLE, self::DEFAULT_SUB_TITLE);
    }

    /**
     * Save the given properties to the database and to the cache.
     *
     * @param array $properties the properties to set
     */
    public function setProperties(array $properties): void
    {
        if (!empty($properties)) {
            // update
            $repository = $this->getRepository();
            foreach ($properties as $key => $value) {
                $this->saveProperty($repository, $key, $value);
            }

            // save changes
            $this->manager->flush();

            // reload
            $this->updateAdapter();
        }
    }

    /**
     * Check if cache is up to date and if not load data from respository.
     *
     * @return AdapterInterface the adapter
     */
    private function getAdapter(): AdapterInterface
    {
        $item = $this->adapter->getItem(self::CACHE_SAVED);
        if (!$item->isHit() || !(bool) ($item->get())) {
            $this->logInfo('Loaded properties from database.', $this->getLogContext());

            return $this->updateAdapter();
        }

        return $this->adapter;
    }

    /**
     * Gets a boolean property.
     *
     * @param string $name    the property name to search for
     * @param bool   $default the default value if the property is not found
     *
     * @return bool the boolean value, if found; the default value otherwise
     */
    private function getBoolean(string $name, bool $default = false): bool
    {
        return (bool) $this->getItemValue($name, $default);
    }

    /**
     * Gets a date property.
     *
     * @param string         $name    the property name to search for
     * @param \DateTime|null $default the default value if the property is not found
     *
     * @return \DateTime|null the date value, if found; the default value otherwise
     */
    private function getDate(string $name, ?\DateTime $default = null): ?\DateTime
    {
        $timestamp = $this->getInteger($name);
        if (Property::FALSE_VALUE !== $timestamp) {
            return \DateTime::createFromFormat('U', (string) $timestamp);
        }

        return $default;
    }

    /**
     * Gets a float property.
     *
     * @param string $name    the property name to search for
     * @param float  $default the default value if the property is not found
     *
     * @return float the float value, if found; the default value otherwise
     */
    private function getFloat(string $name, float $default = 0): float
    {
        return (float) $this->getItemValue($name, $default);
    }

    /**
     * Gets a integer property.
     *
     * @param string $name    the property name to search for
     * @param int    $default the default value if the property is not found
     *
     * @return int the integer value, if found; the default value otherwise
     */
    private function getInteger(string $name, int $default = 0): int
    {
        return (int) $this->getItemValue($name, $default);
    }

    /**
     * Gets an item value.
     *
     * @param string $name    the item name
     * @param mixed  $default the default value if the item is not found
     *
     * @return mixed the value, if hit; the default value otherwise
     */
    private function getItemValue(string $name, $default)
    {
        $item = $this->getAdapter()->getItem($name);
        if ($item->isHit()) {
            return $item->get();
        }

        return $default;
    }

    /**
     * Gets the log context.
     */
    private function getLogContext(): array
    {
        return [
            'service' => Utils::getShortName($this),
            'adapter' => Utils::getShortName($this->adapter),
        ];
    }

    /**
     * Gets the property repository.
     */
    private function getRepository(): PropertyRepository
    {
        return $this->manager->getRepository(Property::class);
    }

    /**
     * Gets a string property.
     *
     * @param string      $name    the property name to search for
     * @param string|null $default the default value if the property is not found
     *
     * @return string|null the string value, if found; the default value otherwise
     */
    private function getString(string $name, ?string $default = null): ?string
    {
        return (string) $this->getItemValue($name, $default);
    }

    /**
     * Sets a cache item value to be persisted later.
     *
     * @param AdapterInterface $adapter the cache adapter
     * @param string           $key     the key for which to return the corresponding cache item
     * @param mixed            $value   the value to set
     *
     * @return bool false if the item could not be queued or if a commit was attempted and failed; true otherwise
     */
    private function saveDeferredItem(AdapterInterface $adapter, string $key, $value): bool
    {
        $item = $adapter->getItem($key);
        $item->expiresAfter(self::CACHE_TIMEOUT)
            ->set($value);

        if (!$adapter->saveDeferred($item)) {
            $this->logWarning("Unable to deferred persist item '{$key}'.", $this->getLogContext());

            return false;
        }

        return true;
    }

    /**
     * Update a property without flusing changes.
     *
     * @param PropertyRepository $repository the property repository
     * @param string             $name       the property name
     * @param mixed              $value      the property value
     */
    private function saveProperty(PropertyRepository $repository, string $name, $value): void
    {
        // get or create property
        $property = $repository->findOneByName($name);
        if (null === $property) {
            $property = Property::create($name);
            $this->manager->persist($property);
        }

        // set value
        $property->setValue($value);
    }

    /**
     * Update the content of the cache from the repository.
     *
     * @return AdapterInterface the adapter
     */
    private function updateAdapter(): AdapterInterface
    {
        // clear
        $adapter = $this->adapter;
        if (!$adapter->clear()) {
            $this->logWarning('Error while clearing properties cache.', $this->getLogContext());
        }

        // create items
        $properties = $this->getRepository()->findAll();
        foreach ($properties as $property) {
            $this->saveDeferredItem($adapter, $property->getName(), $property->getString());
        }
        $this->saveDeferredItem($adapter, self::CACHE_SAVED, true);

        // save
        if (!$adapter->commit()) {
            $this->logWarning('Unable to commit changes to the cache.', $this->getLogContext());
        }

        return $adapter;
    }
}
