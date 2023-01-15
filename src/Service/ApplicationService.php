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

namespace App\Service;

use App\Entity\AbstractEntity;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Property;
use App\Enums\EntityAction;
use App\Enums\MessagePosition;
use App\Enums\StrengthLevel;
use App\Enums\TableView;
use App\Interfaces\PropertyServiceInterface;
use App\Model\CustomerInformation;
use App\Model\Role;
use App\Repository\PropertyRepository;
use App\Traits\PropertyTrait;
use App\Util\RoleBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to manage application properties.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ApplicationService implements PropertyServiceInterface, ServiceSubscriberInterface
{
    use PropertyTrait;

    public function __construct(
        private readonly EntityManagerInterface $manager,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
        #[Target('application_cache')]
        CacheItemPoolInterface $cache
    ) {
        $this->setCacheAdapter($cache);
    }

    /**
     * Gets the administrator role rights.
     *
     * @return int[] the rights
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getAdminRights(): array
    {
        return $this->getAdminRole()->getRights();
    }

    /**
     * Gets the administrator role.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getAdminRole(): Role
    {
        $role = RoleBuilder::getRoleAdmin();
        $rights = $this->getPropertyArray(self::P_ADMIN_RIGHTS, $role->getRights());
        $role->setRights($rights);

        return $role;
    }

    /**
     * Gets the last archive calculation.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getArchiveCalculation(): ?\DateTimeInterface
    {
        return $this->getPropertyDate(self::P_DATE_CALCULATION);
    }

    /**
     * {@inheritDoc}
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
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCustomerAddress(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_ADDRESS);
    }

    /**
     * Gets the customer e-mail.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCustomerEmail(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_EMAIL);
    }

    /**
     * Gets the customer fax number.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCustomerFax(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_FAX);
    }

    /**
     * Gets the customer name.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCustomerName(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_NAME);
    }

    /**
     * Gets the customer phone number.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCustomerPhone(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_PHONE);
    }

    /**
     * Gets the customer website (URL).
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCustomerUrl(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_URL);
    }

    /**
     * Gets the customer zip code and city.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCustomerZipCity(): ?string
    {
        return $this->getPropertyString(self::P_CUSTOMER_ZIP_CITY);
    }

    /**
     * Gets the default category.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDefaultCategory(): ?Category
    {
        $id = $this->getDefaultCategoryId();
        if (0 !== $id) {
            $category = $this->manager->getRepository(Category::class)->find($id);
            if ($category instanceof Category) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Gets the default category identifier.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDefaultCategoryId(): int
    {
        return $this->getPropertyInteger(self::P_DEFAULT_CATEGORY);
    }

    /**
     * Gets the default product.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDefaultProduct(): ?Product
    {
        $id = $this->getDefaultProductId();
        if (0 !== $id) {
            $product = $this->manager->getRepository(Product::class)->find($id);
            if ($product instanceof Product) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Gets the default product identifier.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDefaultProductId(): int
    {
        return $this->getPropertyInteger(self::P_DEFAULT_PRODUCT);
    }

    /**
     * Gets the default product quantity.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDefaultQuantity(): float
    {
        return $this->getPropertyFloat(self::P_DEFAULT_PRODUCT_QUANTITY);
    }

    /**
     * Gets the default calculation state.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDefaultState(): ?CalculationState
    {
        $id = $this->getDefaultStateId();
        if (0 !== $id) {
            $state = $this->manager->getRepository(CalculationState::class)->find($id);
            if ($state instanceof CalculationState) {
                return $state;
            }
        }

        return null;
    }

    /**
     * Gets the default calculation state identifier.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDefaultStateId(): int
    {
        return $this->getPropertyInteger(self::P_DEFAULT_STATE);
    }

    /**
     * Gets the default values.
     *
     * @return array<string, mixed>
     */
    public function getDefaultValues(): array
    {
        $properties = [
            // margin
            self::P_MIN_MARGIN => self::DEFAULT_MIN_MARGIN,
            // default product
            self::P_DEFAULT_PRODUCT_EDIT => true,
            self::P_DEFAULT_PRODUCT_QUANTITY => 0,
            // display and edit entities
            self::P_DISPLAY_MODE => self::DEFAULT_DISPLAY_MODE,
            self::P_EDIT_ACTION => self::DEFAULT_ACTION,
            // notification
            self::P_MESSAGE_POSITION => self::DEFAULT_MESSAGE_POSITION,
            self::P_MESSAGE_TIMEOUT => self::DEFAULT_MESSAGE_TIMEOUT,
            self::P_MESSAGE_TITLE => self::DEFAULT_MESSAGE_TITLE,
            self::P_MESSAGE_SUB_TITLE => self::DEFAULT_MESSAGE_SUB_TITLE,
            self::P_MESSAGE_PROGRESS => self::DEFAULT_MESSAGE_PROGRESS,
            self::P_MESSAGE_ICON => self::DEFAULT_MESSAGE_ICON,
            self::P_MESSAGE_CLOSE => self::DEFAULT_MESSAGE_CLOSE,
            // home page
            self::P_PANEL_CALCULATION => self::DEFAULT_PANEL_CALCULATION,
            self::P_PANEL_CATALOG => true,
            self::P_PANEL_STATE => true,
            self::P_PANEL_MONTH => true,
            self::P_STATUS_BAR => true,
            // document options
            self::P_QR_CODE => self::DEFAULT_QR_CODE,
            self::P_PRINT_ADDRESS => self::DEFAULT_PRINT_ADDRESS,
            // security
            self::P_STRENGTH_LEVEL => StrengthLevel::NONE,
            self::P_DISPLAY_CAPTCHA => !$this->debug,
        ];
        // password options
        foreach (self::PASSWORD_OPTIONS as $option) {
            $properties[$option] = false;
        }

        return $properties;
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayMode(): TableView
    {
        $default = self::DEFAULT_DISPLAY_MODE;
        $value = (string) $this->getPropertyString(self::P_DISPLAY_MODE, $default->value);

        return TableView::tryFrom($value) ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function getEditAction(): EntityAction
    {
        $default = self::DEFAULT_ACTION;
        $value = (string) $this->getPropertyString(self::P_EDIT_ACTION, $default->value);

        return EntityAction::tryFrom($value) ?? $default;
    }

    /**
     * Gets the last import of Swiss cities.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getLastImport(): ?\DateTimeInterface
    {
        return $this->getPropertyDate(self::P_DATE_IMPORT);
    }

    /**
     * {@inheritDoc}
     */
    public function getMessagePosition(): MessagePosition
    {
        $default = self::DEFAULT_MESSAGE_POSITION;
        $value = (string) $this->getPropertyString(self::P_MESSAGE_POSITION, $default->value);

        return MessagePosition::tryFrom($value) ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function getMessageProgress(): int
    {
        return $this->getPropertyInteger(self::P_MESSAGE_PROGRESS, self::DEFAULT_MESSAGE_PROGRESS);
    }

    /**
     * {@inheritDoc}
     */
    public function getMessageTimeout(): int
    {
        return $this->getPropertyInteger(self::P_MESSAGE_TIMEOUT, self::DEFAULT_MESSAGE_TIMEOUT);
    }

    /**
     * Gets the minimum margin, in percent, for a calculation (default: 3.0 = 300%).
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMinMargin(): float
    {
        return $this->getPropertyFloat(self::P_MIN_MARGIN, self::DEFAULT_MIN_MARGIN);
    }

    /**
     * {@inheritDoc}
     */
    public function getPanelCalculation(): int
    {
        return $this->getPropertyInteger(self::P_PANEL_CALCULATION, self::DEFAULT_PANEL_CALCULATION);
    }

    /**
     * Gets all properties except date values.
     *
     * @return array<string, mixed>
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getProperties(): array
    {
        $properties = \array_merge(
            $this->loadProperties(),
            [
                // customer
                self::P_CUSTOMER_NAME => $this->getCustomerName(),
                self::P_CUSTOMER_ADDRESS => $this->getCustomerAddress(),
                self::P_CUSTOMER_ZIP_CITY => $this->getCustomerZipCity(),
                self::P_CUSTOMER_PHONE => $this->getCustomerPhone(),
                self::P_CUSTOMER_FAX => $this->getCustomerFax(),
                self::P_CUSTOMER_EMAIL => $this->getCustomerEmail(),
                self::P_CUSTOMER_URL => $this->getCustomerUrl(),
                // security
                self::P_DISPLAY_CAPTCHA => $this->isDisplayCaptcha(),
                self::P_STRENGTH_LEVEL => $this->getStrengthLevel(),
                // default state, category and margin
                self::P_DEFAULT_STATE => $this->getDefaultState(),
                self::P_DEFAULT_CATEGORY => $this->getDefaultCategory(),
                self::P_MIN_MARGIN => $this->getMinMargin(),
                // default product
                self::P_DEFAULT_PRODUCT => $this->getDefaultProduct(),
                self::P_DEFAULT_PRODUCT_QUANTITY => $this->getDefaultQuantity(),
                self::P_DEFAULT_PRODUCT_EDIT => $this->isDefaultEdit(),
            ]
        );
        foreach (self::PASSWORD_OPTIONS as $option) {
            $properties[$option] = $this->getPropertyBoolean($option);
        }

        return $properties;
    }

    /**
     * Gets the password strength level.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getStrengthLevel(): StrengthLevel
    {
        $default = self::DEFAULT_STRENGTH_LEVEL;
        $value = $this->getPropertyInteger(self::P_STRENGTH_LEVEL, $default->value);

        return StrengthLevel::tryFrom($value) ?? $default;
    }

    /**
     * Gets the last products update.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getUpdateProducts(): ?\DateTimeInterface
    {
        return $this->getPropertyDate(self::P_DATE_PRODUCT);
    }

    /**
     * Gets the user role rights.
     *
     * @return int[] the rights
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getUserRights(): array
    {
        return $this->getUserRole()->getRights();
    }

    /**
     * Gets the user role.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getUserRole(): Role
    {
        $role = RoleBuilder::getRoleUser();
        $rights = $this->getPropertyArray(self::P_USER_RIGHTS, $role->getRights());
        $role->setRights($rights);

        return $role;
    }

    /**
     * Return the debug state.
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Gets a value indicating if the default product (if any)  must be edited
     * when a new calculation is created.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isDefaultEdit(): bool
    {
        return $this->getPropertyBoolean(self::P_DEFAULT_PRODUCT_EDIT, self::DEFAULT_PRODUCT_EDIT);
    }

    /**
     * Gets a value indicating the image captcha is displayed when login.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isDisplayCaptcha(): bool
    {
        return $this->getPropertyBoolean(self::P_DISPLAY_CAPTCHA, !$this->debug);
    }

    /**
     * Returns if the given value is below the minimum margin.
     *
     * @param float|Calculation $value the calculation or the margin to be tested
     *
     * @throws \Psr\Cache\InvalidArgumentException
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
     * {@inheritDoc}
     */
    public function isMessageClose(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_CLOSE, self::DEFAULT_MESSAGE_CLOSE);
    }

    /**
     * {@inheritDoc}
     */
    public function isMessageIcon(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_ICON, self::DEFAULT_MESSAGE_ICON);
    }

    /**
     * {@inheritDoc}
     */
    public function isMessageSubTitle(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_SUB_TITLE, self::DEFAULT_MESSAGE_SUB_TITLE);
    }

    /**
     * {@inheritDoc}
     */
    public function isMessageTitle(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_TITLE, self::DEFAULT_MESSAGE_TITLE);
    }

    /**
     * {@inheritDoc}
     */
    public function isPanelCatalog(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_CATALOG, true);
    }

    /**
     * {@inheritDoc}
     */
    public function isPanelMonth(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_MONTH, true);
    }

    /**
     * {@inheritDoc}
     */
    public function isPanelState(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_STATE, true);
    }

    /**
     * {@inheritDoc}
     */
    public function isPrintAddress(): bool
    {
        return $this->getPropertyBoolean(self::P_PRINT_ADDRESS, self::DEFAULT_PRINT_ADDRESS);
    }

    /**
     * {@inheritDoc}
     */
    public function isQrCode(): bool
    {
        return $this->getPropertyBoolean(self::P_QR_CODE, self::DEFAULT_QR_CODE);
    }

    /**
     * {@inheritDoc}
     */
    public function isStatusBar(): bool
    {
        return $this->getPropertyBoolean(self::P_STATUS_BAR, true);
    }

    /**
     * Remove the give property.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function removeProperty(string $name): self
    {
        $repository = $this->getRepository();
        $property = $repository->findOneByName($name);
        if (null !== $property) {
            $repository->remove($property);
            $this->updateAdapter();
        }

        return $this;
    }

    public function setProperties(array $properties, ?array $defaultValues = null): static
    {
        if (!empty($properties)) {
            $repository = $this->getRepository();
            $defaultValues ??= $this->getDefaultValues();

            /** @psalm-var mixed $value */
            foreach ($properties as $key => $value) {
                $this->saveProperty($key, $value, $defaultValues, $repository);
            }
            $this->manager->flush();
            $this->updateAdapter();
        }

        return $this;
    }

    /**
     * Remove the default category if deleted from the database.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function updateDeletedCategory(Category $category): void
    {
        $this->updateDeletedEntity(self::P_DEFAULT_CATEGORY, $category);
    }

    /**
     * Remove the default product if deleted from the database.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function updateDeletedProduct(Product $product): void
    {
        $this->updateDeletedEntity(self::P_DEFAULT_PRODUCT, $product);
    }

    /**
     * Remove the default calculation state if deleted from the database.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function updateDeletedState(CalculationState $state): void
    {
        $this->updateDeletedEntity(self::P_DEFAULT_STATE, $state);
    }

    protected function updateAdapter(): void
    {
        $properties = $this->manager->getRepository(Property::class)->findAll();
        $this->saveProperties($properties);
    }

    /**
     * Gets the property repository.
     */
    private function getRepository(): PropertyRepository
    {
        return $this->manager->getRepository(Property::class);
    }

    /**
     * Update a property without saving changes to database.
     */
    private function saveProperty(string $name, mixed $value, array $defaultValues, PropertyRepository $repository): void
    {
        $property = $repository->findOneByName($name);
        if ($this->isDefaultValue($defaultValues, $name, $value)) {
            // remove if present
            if ($property instanceof Property) {
                $repository->remove($property, false);
            }
        } else {
            // create if needed
            if (!$property instanceof Property) {
                $property = Property::instance($name);
                $repository->add($property, false);
            }
            $property->setValue($value);
        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function updateDeletedEntity(string $name, AbstractEntity $entity): void
    {
        if ($this->getPropertyInteger($name) === $entity->getId()) {
            $this->setProperty($name, null);
        }
    }
}
