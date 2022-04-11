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
use App\Entity\Product;
use App\Entity\Property;
use App\Interfaces\ActionInterface;
use App\Interfaces\ApplicationServiceInterface;
use App\Interfaces\StrengthInterface;
use App\Model\CustomerInformation;
use App\Model\Role;
use App\Repository\PropertyRepository;
use App\Security\EntityVoter;
use App\Traits\LoggerTrait;
use App\Util\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Service to manage application properties.
 *
 * @author Laurent Muller
 */
class ApplicationService extends AppVariable implements LoggerAwareInterface, ApplicationServiceInterface
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

    private readonly CacheItemPoolInterface $adapter;

    /**
     * Constructor.
     */
    public function __construct(private readonly EntityManagerInterface $manager, KernelInterface $kernel, LoggerInterface $logger)
    {
        $this->adapter = AbstractAdapter::createSystemCache(self::CACHE_NAME_SPACE, self::CACHE_TIMEOUT, '', $kernel->getCacheDir(), $logger);

        $this->setLogger($logger);
        $this->setDebug($kernel->isDebug());
        $this->setEnvironment($kernel->getEnvironment());
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
        /** @psalm-var int[] $rights */
        $rights = $this->getPropertyArray(self::P_ADMIN_RIGHTS, $role->getRights());
        $role->setRights($rights);

        return $role;
    }

    /**
     * Gets this cache class short name.
     */
    public function getCacheClass(): string
    {
        return Utils::getShortName($this->adapter);
    }

    /**
     * Gets the customer information.
     */
    public function getCustomer(): CustomerInformation
    {
        $info = new CustomerInformation();
        $info->setName($this->getCustomerName())
            ->setAddress($this->getCustomerAddress())
            ->setZipCity($this->getCustomerZipCity())
            ->setPhone($this->getCustomerPhone())
            ->setFax($this->getCustomerFax())
            ->setEmail($this->getCustomerEmail())
            ->setUrl($this->getCustomerUrl())
            ->setPrintAddress($this->isPrintAddress());

        return $info;
    }

    /**
     * Gets the customer address.
     */
    public function getCustomerAddress(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_ADDRESS);
    }

    /**
     * Gets the customer e-mail.
     */
    public function getCustomerEmail(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_EMAIL);
    }

    /**
     * Gets the customer fax number.
     */
    public function getCustomerFax(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_FAX);
    }

    /**
     * Gets the customer name.
     */
    public function getCustomerName(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_NAME);
    }

    /**
     * Gets the customer phone number.
     */
    public function getCustomerPhone(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_PHONE);
    }

    /**
     * Gets the customer website (URL).
     */
    public function getCustomerUrl(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_URL);
    }

    /**
     * Gets the customer zip code and city.
     */
    public function getCustomerZipCity(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_ZIP_CITY);
    }

    /**
     * Gets the default category.
     */
    public function getDefaultCategory(): ?Category
    {
        $id = $this->getDefaultCategoryId();
        if (0 !== $id) {
            /** @psalm-var \Doctrine\ORM\EntityRepository<Category> $repository */
            $repository = $this->manager->getRepository(Category::class);
            $category = $repository->find($id);
            if ($category instanceof Category) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Gets the default category identifier.
     */
    public function getDefaultCategoryId(): int
    {
        return $this->getPropertyInteger(self::P_DEFAULT_CATEGORY);
    }

    /**
     * Gets the default product.
     */
    public function getDefaultProduct(): ?Product
    {
        $id = $this->getDefaultProductId();
        if (0 !== $id) {
            /** @psalm-var \Doctrine\ORM\EntityRepository<Product> $repository */
            $repository = $this->manager->getRepository(Product::class);
            $product = $repository->find($id);
            if ($product instanceof Product) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Gets the default product identifier.
     */
    public function getDefaultProductId(): int
    {
        return $this->getPropertyInteger(self::P_DEFAULT_PRODUCT);
    }

    /**
     * Gets the default product quantity.
     */
    public function getDefaultQuantity(): float
    {
        return $this->getPropertyFloat(self::P_DEFAULT_PRODUCT_QUANTITY);
    }

    /**
     * Gets the default calculation state.
     */
    public function getDefaultState(): ?CalculationState
    {
        $id = $this->getDefaultStateId();
        if (0 !== $id) {
            /** @psalm-var \Doctrine\ORM\EntityRepository<CalculationState> $repository */
            $repository = $this->manager->getRepository(CalculationState::class);
            $state = $repository->find($id);
            if ($state instanceof CalculationState) {
                return $state;
            }
        }

        return null;
    }

    /**
     * Gets the default calculation state identifier.
     */
    public function getDefaultStateId(): int
    {
        return $this->getPropertyInteger(self::P_DEFAULT_STATE);
    }

    /**
     * Gets a value indicating table display mode.
     */
    public function getDisplayMode(): string
    {
        return (string) $this->getPropertyString(self::P_DISPLAY_MODE, self::DEFAULT_DISPLAY_MODE);
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
        return (string) $this->getPropertyString(self::P_EDIT_ACTION, self::DEFAULT_ACTION);
    }

    /**
     * Gets the last import of Swiss cities.
     */
    public function getLastImport(): ?\DateTimeInterface
    {
        return $this->getPropertyDate(self::P_LAST_IMPORT);
    }

    /**
     * Gets the position of the flashbag messages (default: 'bottom-right').
     */
    public function getMessagePosition(): string
    {
        return (string) $this->getPropertyString(self::P_MESSAGE_POSITION, self::DEFAULT_MESSAGE_POSITION);
    }

    /**
     * Gets the timeout, in milliseconds, of the flashbag messages (default: 4000 ms).
     */
    public function getMessageTimeout(): int
    {
        return $this->getPropertyInteger(self::P_MESSAGE_TIMEOUT, self::DEFAULT_MESSAGE_TIMEOUT);
    }

    /**
     * Gets the minimum margin, in percent, for a calculation (default: 3.0 = 300%).
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
        return $this->getPropertyInteger(self::P_MIN_STRENGTH, StrengthInterface::LEVEL_NONE);
    }

    /**
     * Returns a value indicating number of displayed calculation in the home page.
     */
    public function getPanelCalculation(): int
    {
        return $this->getPropertyInteger(self::P_PANEL_CALCULATION, self::DEFAULT_PANEL_CALCULATION);
    }

    /**
     * Gets all properties.
     *
     * @param string[] $excluded the property keys to exclude
     */
    public function getProperties(array $excluded = []): array
    {
        // reload data
        $this->updateAdapter();

        $result = [
            self::P_CUSTOMER_NAME => $this->getCustomerName(),
            self::P_CUSTOMER_ADDRESS => $this->getCustomerAddress(),
            self::P_CUSTOMER_ZIP_CITY => $this->getCustomerZipCity(),
            self::P_CUSTOMER_PHONE => $this->getCustomerPhone(),
            self::P_CUSTOMER_FAX => $this->getCustomerFax(),
            self::P_CUSTOMER_EMAIL => $this->getCustomerEmail(),
            self::P_CUSTOMER_URL => $this->getCustomerUrl(),

            self::P_EDIT_ACTION => $this->getEditAction(),
            self::P_DEFAULT_STATE => $this->getDefaultState(),
            self::P_DEFAULT_CATEGORY => $this->getDefaultCategory(),

            self::P_MESSAGE_ICON => $this->isMessageIcon(),
            self::P_MESSAGE_TITLE => $this->isMessageTitle(),
            self::P_MESSAGE_SUB_TITLE => $this->isMessageSubTitle(),
            self::P_MESSAGE_CLOSE => $this->isMessageClose(),
            self::P_MESSAGE_PROGRESS => $this->isMessageProgress(),
            self::P_MESSAGE_POSITION => $this->getMessagePosition(),
            self::P_MESSAGE_TIMEOUT => $this->getMessageTimeout(),

            self::P_UPDATE_PRODUCTS => $this->getUpdateProducts(),
            self::P_LAST_IMPORT => $this->getLastImport(),

            self::P_MIN_MARGIN => $this->getMinMargin(),

            self::P_DISPLAY_MODE => $this->getDisplayMode(),
            self::P_DISPLAY_CAPTCHA => $this->isDisplayCaptcha(),

            self::P_QR_CODE => $this->isQrCode(),
            self::P_PRINT_ADDRESS => $this->isPrintAddress(),

            self::P_DEFAULT_PRODUCT => $this->getDefaultProduct(),
            self::P_DEFAULT_PRODUCT_QUANTITY => $this->getDefaultQuantity(),
            self::P_DEFAULT_PRODUCT_EDIT => $this->isDefaultEdit(),

            self::P_PANEL_CALCULATION => $this->getPanelCalculation(),
            self::P_PANEL_STATE => $this->isPanelState(),
            self::P_PANEL_MONTH => $this->isPanelMonth(),
            self::P_PANEL_CATALOG => $this->isPanelCatalog(),
        ];

        // exlude keys
        if (!empty($excluded)) {
            return \array_diff_key($result, \array_flip($excluded));
        }

        return $result;
    }

    /**
     * Gets an array property.
     *
     * @param string $name    the property name to search for
     * @param array  $default the default value if the property is not found
     */
    public function getPropertyArray(string $name, array $default): array
    {
        /** @psalm-var mixed $value */
        $value = $this->getItemValue($name, $default);
        if (\is_string($value)) {
            /** @psalm-var mixed $value */
            $value = \json_decode($value, true);
            if (\JSON_ERROR_NONE !== \json_last_error()) {
                return $default;
            }
        }
        if (\is_array($value) && \count($value) === \count($default)) {
            return $value;
        }

        return $default;
    }

    /**
     * Gets a date property.
     *
     * @param string                  $name    the property name to search for
     * @param \DateTimeInterface|null $default the default value if the property is not found
     */
    public function getPropertyDate(string $name, ?\DateTimeInterface $default = null): ?\DateTimeInterface
    {
        $timestamp = $this->getPropertyInteger($name);
        if (Property::FALSE_VALUE !== $timestamp) {
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
     */
    public function getPropertyString(string $name, ?string $default = null): ?string
    {
        /** @psalm-var mixed $value */
        $value = $this->getItemValue($name, $default);
        if (\is_string($value)) {
            return $value;
        }

        return $default;
    }

    /**
     * Gets the last products update.
     */
    public function getUpdateProducts(): ?\DateTimeInterface
    {
        return $this->getPropertyDate(self::P_UPDATE_PRODUCTS);
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
        /** @psalm-var int[] $rights */
        $rights = $this->getPropertyArray(self::P_USER_RIGHTS, $role->getRights());
        $role->setRights($rights);

        return $role;
    }

    /**
     * Returns a value indicating if the default action is to edit the entity.
     */
    public function isActionEdit(): bool
    {
        return ActionInterface::ACTION_EDIT === $this->getEditAction();
    }

    /**
     * Returns a value indicating if the default action is to do nothing.
     */
    public function isActionNone(): bool
    {
        return ActionInterface::ACTION_NONE === $this->getEditAction();
    }

    /**
     * Returns a value indicating if the default action is to show the entity.
     */
    public function isActionShow(): bool
    {
        return ActionInterface::ACTION_SHOW === $this->getEditAction();
    }

    /**
     * Gets the default product edit on new calculation.
     */
    public function isDefaultEdit(): bool
    {
        return $this->isPropertyBoolean(self::P_DEFAULT_PRODUCT_EDIT, self::DEFAULT_PRODUCT_EDIT);
    }

    /**
     * Gets a value indicating the image captcha is displayed when login.
     */
    public function isDisplayCaptcha(): bool
    {
        return $this->isPropertyBoolean(self::P_DISPLAY_CAPTCHA, !$this->getDebug());
    }

    /**
     * Returns if the given value is below the minimum margin.
     *
     * @param float|Calculation $value the calculation or the margin to be tested
     */
    public function isMarginBelow(float|Calculation $value): bool
    {
        if ($value instanceof Calculation) {
            return $value->isMarginBelow($this->getMinMargin());
        } else {
            return $value < $this->getMinMargin();
        }
    }

    /**
     * Returns if the flash bag message icon is displayed (default: true).
     */
    public function isMessageClose(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_CLOSE, self::DEFAULT_MESSAGE_CLOSE);
    }

    /**
     * Returns if the flash bag message icon is displayed (default: true).
     */
    public function isMessageIcon(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_ICON, self::DEFAULT_MESSAGE_ICON);
    }

    /**
     * Returns if the flash bag message progress bar is displayed (default: true).
     */
    public function isMessageProgress(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_PROGRESS, self::DEFAULT_PROGRESS);
    }

    /**
     * Returns if the flash bag message subtitle is displayed (default: true).
     */
    public function isMessageSubTitle(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_SUB_TITLE, self::DEFAULT_MESSAGE_SUB_TITLE);
    }

    /**
     * Returns if the flash bag message title is displayed (default: true).
     */
    public function isMessageTitle(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_TITLE, self::DEFAULT_MESSAGE_TITLE);
    }

    /**
     * Returns a value indicating if the catalog panel is displayed in the home page.
     */
    public function isPanelCatalog(): bool
    {
        return $this->isPropertyBoolean(self::P_PANEL_CATALOG, true);
    }

    /**
     * Returns a value indicating if the month panel is displayed in the home page.
     */
    public function isPanelMonth(): bool
    {
        return $this->isPropertyBoolean(self::P_PANEL_MONTH, true);
    }

    /**
     * Returns a value indicating if the state panel is displayed in the home page.
     */
    public function isPanelState(): bool
    {
        return $this->isPropertyBoolean(self::P_PANEL_STATE, true);
    }

    /**
     * Gets a value indicating if the customer address is output within the PDF documents.
     */
    public function isPrintAddress(): bool
    {
        return $this->isPropertyBoolean(self::P_PRINT_ADDRESS, self::DEFAULT_PRINT_ADDRESS);
    }

    /**
     * Gets a boolean property.
     *
     * @param string $name    the property name to search for
     * @param bool   $default the default value if the property is not found
     */
    public function isPropertyBoolean(string $name, bool $default = false): bool
    {
        return (bool) $this->getItemValue($name, $default);
    }

    /**
     * Gets a value indicating if a QR-Code is output at the end of the PDF documents.
     */
    public function isQrCode(): bool
    {
        return $this->isPropertyBoolean(self::P_QR_CODE, self::DEFAULT_QR_CODE);
    }

    /**
     * Save the given properties to the database and to the cache.
     *
     * @param array<string, mixed> $properties the properties to set
     */
    public function setProperties(array $properties): self
    {
        if (!empty($properties)) {
            $repository = $this->getRepository();
            /** @psalm-var mixed $value */
            foreach ($properties as $key => $value) {
                $this->saveProperty($repository, $key, $value);
            }

            // save changes
            $this->manager->flush();

            // reload
            $this->updateAdapter();
        }

        return $this;
    }

    /**
     * Sets a single property value.
     *
     * @param string $name  the property name
     * @param mixed  $value the property value
     */
    public function setProperty(string $name, mixed $value): self
    {
        return $this->setProperties([$name => $value]);
    }

    /**
     * Check if cache is up-to-date and if not load data from repository.
     */
    private function getAdapter(): CacheItemPoolInterface
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
    private function getItemValue(string $name, mixed $default)
    {
        $item = $this->getAdapter()->getItem($name);
        if ($item->isHit()) {
            return $item->get();
        }

        return $default;
    }

    /**
     * Gets the log context.
     *
     * @psalm-return array<string, string>
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
     *
     * @psalm-suppress UnnecessaryVarAnnotation
     */
    private function getRepository(): PropertyRepository
    {
        /** @psalm-var PropertyRepository $repository */
        $repository = $this->manager->getRepository(Property::class);

        return $repository;
    }

    /**
     * Sets a cache item value to be persisted later.
     *
     * @param CacheItemPoolInterface $adapter the cache adapter
     * @param string                 $key     the key for which to return the corresponding cache item
     * @param mixed                  $value   the value to set
     *
     * @return bool false if the item could not be queued or if a commit was attempted and failed; true otherwise
     */
    private function saveDeferredItem(CacheItemPoolInterface $adapter, string $key, mixed $value): bool
    {
        $item = $adapter->getItem($key);
        $item->expiresAfter(self::CACHE_TIMEOUT)
            ->set($value);

        if (!$adapter->saveDeferred($item)) {
            $this->logWarning("Unable to deferred persist item '$key'.", $this->getLogContext());

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
    private function saveProperty(PropertyRepository $repository, string $name, mixed $value): void
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
     */
    private function updateAdapter(): CacheItemPoolInterface
    {
        // clear
        $adapter = $this->adapter;
        if (!$adapter->clear()) {
            $this->logWarning('Error while clearing properties cache.', $this->getLogContext());
        }

        // create items
        $properties = $this->manager->getRepository(Property::class)->findAll();
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
