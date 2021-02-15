<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Service;

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Property;
use App\Entity\Role;
use App\Interfaces\ActionInterface;
use App\Interfaces\ApplicationServiceInterface;
use App\Repository\PropertyRepository;
use App\Security\EntityVoter;
use App\Traits\LoggerTrait;
use App\Util\Utils;
use App\Validator\Strength;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Service to manage application properties.
 *
 * @author Laurent Muller
 */
class ApplicationService extends AppVariable implements ApplicationServiceInterface
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

        $this->setDebug($kernel->isDebug());
        $this->setEnvironment($kernel->getEnvironment());

        $dir = $kernel->getCacheDir();
        $this->adapter = AbstractAdapter::createSystemCache(self::CACHE_NAME_SPACE, self::CACHE_TIMEOUT, '', $dir, $logger);
    }

    /**
     * Clear this cache.
     *
     * @return bool true if the cache was successfully cleared; false if there was an error
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
     * @return int[] the rights
     */
    public function getAdminRights(): array
    {
        return $this->getAdminRole()->getRights();
    }

    /**
     * Gets the administrator role.
     */
    public function getAdminRole(): Role
    {
        $role = EntityVoter::getRoleAdmin();
        $rights = $this->getPropertyArray(self::P_ADMIN_RIGHTS, $role->getRights());
        $role->setRights($rights);

        return $role;
    }

    /**
     * Gets this cache class short name.
     *
     * @return string the class name
     */
    public function getCacheClass(): string
    {
        return Utils::getShortName($this->adapter);
    }

    /**
     * Gets the customer name.
     *
     * @return string|null the customer name
     */
    public function getCustomerName(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_NAME);
    }

    /**
     * Gets the customer web site (URL).
     *
     * @return string|null the customer web site
     */
    public function getCustomerUrl(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_URL);
    }

    /**
     * Gets the default category.
     *
     * @return Category|null the category, if any; null otherwise
     */
    public function getDefaultCategory(): ?Category
    {
        $id = $this->getDefaultCategoryId();
        if (!empty($id)) {
            $repository = $this->manager->getRepository(Category::class);

            return $repository->find($id);
        }

        return null;
    }

    /**
     * Gets the default category identifier.
     *
     * @return int the category identifer, if any; 0 otherwise
     */
    public function getDefaultCategoryId(): int
    {
        return $this->getPropertyInteger(self::P_DEFAULT_CATEGORY);
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
        return $this->getPropertyInteger(self::P_DEFAULT_STATE);
    }

    /**
     * Gets the action to trigger within the entities.
     * <p>
     * Possible values are:
     * <ul>
     * <li>'<code>edit</code>': The entity is edited.</li>
     * <li>'<code>show</code>': The entity is show.</li>
     * <li>'<code>none</code>': No action is triggered.</li>
     * </ul>
     * </p>.
     */
    public function getEditAction(): string
    {
        return $this->getPropertyString(self::P_EDIT_ACTION, self::DEFAULT_ACTION);
    }

    /**
     * Gets the last import of Swiss cities.
     *
     * @return \DateTime|null the last import or NULL if none
     */
    public function getLastImport(): ?\DateTime
    {
        return $this->getPropertyDate(self::P_LAST_IMPORT);
    }

    /**
     * Gets the last calculations update.
     *
     * @return \DateTime|null the last update or null if none
     */
    public function getLastUpdate(): ?\DateTime
    {
        return $this->getPropertyDate(self::P_LAST_UPDATE);
    }

    /**
     * Gets the position of the flashbag messages (default: 'bottom-right').
     *
     * @return string the position
     */
    public function getMessagePosition(): string
    {
        return $this->getPropertyString(self::P_MESSAGE_POSITION, self::DEFAULT_POSITION);
    }

    /**
     * Gets the timeout, in milliseconds, of the flashbag messages (default: 4000 ms).
     *
     * @return int the timeout
     */
    public function getMessageTimeout(): int
    {
        return $this->getPropertyInteger(self::P_MESSAGE_TIMEOUT, self::DEFAULT_TIMEOUT);
    }

    /**
     * Gets the minimum margin, in percent, for a calculation.
     */
    public function getMinMargin(): float
    {
        return $this->getPropertyFloat(self::P_MIN_MARGIN, self::DEFAULT_MIN_MARGIN);
    }

    /**
     * Gets the minimum password strength.
     */
    public function getMinStrength(): int
    {
        return $this->getPropertyInteger(self::P_MIN_STRENGTH, Strength::LEVEL_DISABLE);
    }

    /**
     * Gets all properties.
     *
     * @param string[] $exludes the property keys to exclude
     *
     * @return array the properties with names and values
     */
    public function getProperties(array $exludes = []): array
    {
        // reload data
        $this->updateAdapter();

        $result = [
            self::P_CUSTOMER_NAME => $this->getCustomerName(),
            self::P_CUSTOMER_URL => $this->getCustomerUrl(),

            self::P_EDIT_ACTION => $this->getEditAction(),
            self::P_DEFAULT_STATE => $this->getDefaultState(),
            self::P_DEFAULT_CATEGORY => $this->getDefaultCategory(),

            self::P_MESSAGE_POSITION => $this->getMessagePosition(),
            self::P_MESSAGE_TIMEOUT => $this->getMessageTimeout(),
            self::P_MESSAGE_SUB_TITLE => $this->isMessageSubTitle(),

            self::P_LAST_UPDATE => $this->getLastUpdate(),
            self::P_LAST_IMPORT => $this->getLastImport(),

            self::P_MIN_MARGIN => $this->getMinMargin(),

            self::P_DISPLAY_TABULAR => $this->isDisplayTabular(),
            self::P_DISPLAY_CAPTCHA => $this->isDisplayCaptcha(),
        ];

        // exlude keys
        if (!empty($exludes)) {
            return \array_diff_key($result, \array_flip($exludes));
        }

        return $result;
    }

    /**
     * Gets a integer array property.
     *
     * @param string $name    the property name to search for
     * @param int[]  $default the default value if the property is not found
     *
     * @return int[] the integer array values, if found; the default value otherwise
     */
    public function getPropertyArray(string $name, array $default): array
    {
        $value = $this->getItemValue($name, $default);

        if (\is_array($value) && \count($value) === \count($default)) {
            return $value;
        }

        if (\is_string($value)) {
            $values = \json_decode($value);
            if ($values && \count($values) === \count($default)) {
                return $values;
            }
        }

        return $default;
    }

    /**
     * Gets a date property.
     *
     * @param string         $name    the property name to search for
     * @param \DateTime|null $default the default value if the property is not found
     *
     * @return \DateTime|null the date value, if found; the default value otherwise
     */
    public function getPropertyDate(string $name, ?\DateTime $default = null): ?\DateTime
    {
        $timestamp = $this->getPropertyInteger($name);
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
    public function getPropertyFloat(string $name, float $default = 0.0): float
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
    public function getPropertyInteger(string $name, int $default = 0): int
    {
        return (int) $this->getItemValue($name, $default);
    }

    /**
     * Gets a string property.
     *
     * @param string      $name    the property name to search for
     * @param string|null $default the default value if the property is not found
     *
     * @return string|null the string value, if found; the default value otherwise
     */
    public function getPropertyString(string $name, ?string $default = null): ?string
    {
        $value = $this->getItemValue($name, $default);
        if (\is_string($value)) {
            return $value;
        }

        return $default;
    }

    /**
     * Gets the user role rights.
     *
     * @return int[] the rights
     */
    public function getUserRights(): array
    {
        return $this->getUserRole()->getRights();
    }

    /**
     * Gets the user role.
     */
    public function getUserRole(): Role
    {
        $role = EntityVoter::getRoleUser();
        $rights = $this->getPropertyArray(self::P_USER_RIGHTS, $role->getRights());
        $role->setRights($rights);

        return $role;
    }

    /**
     * Returns a value indicating if the default action is to edit the entity.
     *
     * @return bool true to edit the entity
     */
    public function isActionEdit(): bool
    {
        return ActionInterface::ACTION_EDIT === $this->getEditAction();
    }

    /**
     * Returns a value indicating if the default action is to do nothing.
     *
     * @return bool true to do nothing with the entity
     */
    public function isActionNone(): bool
    {
        return ActionInterface::ACTION_NONE === $this->getEditAction();
    }

    /**
     * Returns a value indicating if the default action is to show the entity.
     *
     * @return bool true to show the entity
     */
    public function isActionShow(): bool
    {
        return ActionInterface::ACTION_SHOW === $this->getEditAction();
    }

    /**
     * Gets a value indicating the image captcha is displayed when login.
     *
     * @return bool true to display the image; false to hide
     */
    public function isDisplayCaptcha(): bool
    {
        return $this->isPropertyBoolean(self::P_DISPLAY_CAPTCHA, !$this->getDebug());
    }

    /**
     * Gets a value indicating how entities are displayed.
     *
     * @return bool true, displays the entities in tabular mode; false, displays entities as cards
     */
    public function isDisplayTabular(): bool
    {
        return $this->isPropertyBoolean(self::P_DISPLAY_TABULAR, self::DEFAULT_TABULAR);
    }

    /**
     * Returns if the given value is below the minimum margin.
     *
     * @param Calculation|float $value the calculation or the margin to be tested
     *
     * @return bool true if below the minimum; false otherwise
     */
    public function isMarginBelow($value): bool
    {
        if (\is_float($value)) {
            return $value < $this->getMinMargin();
        } elseif ($value instanceof Calculation) {
            return $value->isMarginBelow($this->getMinMargin());
        } else {
            return false;
        }
    }

    /**
     * Returns if the flashbag message sub-title is displayed (default: true).
     *
     * @return bool true if displayed
     */
    public function isMessageSubTitle(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_SUB_TITLE, self::DEFAULT_SUB_TITLE);
    }

    /**
     * Gets a boolean property.
     *
     * @param string $name    the property name to search for
     * @param bool   $default the default value if the property is not found
     *
     * @return bool the boolean value, if found; the default value otherwise
     */
    public function isPropertyBoolean(string $name, bool $default = false): bool
    {
        return (bool) $this->getItemValue($name, $default);
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
        /** @var PropertyRepository $repository */
        $repository = $this->manager->getRepository(Property::class);

        return $repository;
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
