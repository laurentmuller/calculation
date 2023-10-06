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
use App\Traits\MathTrait;
use App\Traits\PropertyServiceTrait;
use App\Utils\StringUtils;
use App\Validator\Password;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to manage application properties.
 */
class ApplicationService implements PropertyServiceInterface, ServiceSubscriberInterface
{
    use MathTrait;
    use PropertyServiceTrait;

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly RoleBuilderService $builder,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
        #[Target('application_cache')]
        CacheItemPoolInterface $cacheItemPool
    ) {
        $this->setCacheItemPool($cacheItemPool);
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
        $role = $this->builder->getRoleAdmin();
        $rights = $this->getPropertyArray(self::P_ADMIN_RIGHTS, $role->getRights());
        $role->setRights($rights);

        return $role;
    }

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
        return $this->findEntity(self::P_DEFAULT_CATEGORY, Category::class);
    }

    /**
     * Gets the default product.
     */
    public function getDefaultProduct(): ?Product
    {
        return $this->findEntity(self::P_PRODUCT_DEFAULT, Product::class);
    }

    /**
     * Gets the default product quantity.
     */
    public function getDefaultQuantity(): float
    {
        return $this->getPropertyFloat(self::P_PRODUCT_QUANTITY);
    }

    /**
     * Gets the default calculation state.
     */
    public function getDefaultState(): ?CalculationState
    {
        return $this->findEntity(self::P_DEFAULT_STATE, CalculationState::class);
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
            self::P_PRODUCT_EDIT => self::DEFAULT_TRUE,
            self::P_PRODUCT_QUANTITY => 0,
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
            self::P_PANEL_CATALOG => self::DEFAULT_TRUE,
            self::P_PANEL_STATE => self::DEFAULT_TRUE,
            self::P_PANEL_MONTH => self::DEFAULT_TRUE,
            self::P_STATUS_BAR => self::DEFAULT_TRUE,
            self::P_DARK_NAVIGATION => self::DEFAULT_TRUE,
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

    public function getDisplayMode(): TableView
    {
        return $this->getPropertyEnum(self::P_DISPLAY_MODE, self::DEFAULT_DISPLAY_MODE);
    }

    public function getEditAction(): EntityAction
    {
        return $this->getPropertyEnum(self::P_EDIT_ACTION, self::DEFAULT_ACTION);
    }

    /**
     * Gets the last archive calculations date.
     */
    public function getLastArchiveCalculations(): ?\DateTimeInterface
    {
        return $this->getPropertyDate(self::P_DATE_CALCULATION);
    }

    /**
     * Gets the last import of Swiss cities.
     */
    public function getLastImport(): ?\DateTimeInterface
    {
        return $this->getPropertyDate(self::P_DATE_IMPORT);
    }

    /**
     * Gets the last update calculations date.
     */
    public function getLastUpdateCalculations(): ?\DateTimeInterface
    {
        return $this->getPropertyDate(self::P_UPDATE_CALCULATION);
    }

    /**
     * Gets the last products update.
     */
    public function getLastUpdateProducts(): ?\DateTimeInterface
    {
        return $this->getPropertyDate(self::P_DATE_PRODUCT);
    }

    public function getMessagePosition(): MessagePosition
    {
        return $this->getPropertyEnum(self::P_MESSAGE_POSITION, self::DEFAULT_MESSAGE_POSITION);
    }

    public function getMessageProgress(): int
    {
        return $this->getPropertyInteger(self::P_MESSAGE_PROGRESS, self::DEFAULT_MESSAGE_PROGRESS);
    }

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

    public function getPanelCalculation(): int
    {
        return $this->getPropertyInteger(self::P_PANEL_CALCULATION, self::DEFAULT_PANEL_CALCULATION);
    }

    /**
     * Create a password contraint with this security properties.
     */
    public function getPasswordConstraint(): Password
    {
        $contraint = new Password();
        foreach (PropertyServiceInterface::PASSWORD_OPTIONS as $option) {
            $property = StringUtils::unicode($option)->trimPrefix('security_')->toString();
            $contraint->{$property} = $this->getPropertyBoolean($option);
        }

        return $contraint;
    }

    /**
     * Gets all properties except date values.
     *
     * @return array<string, mixed>
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
                self::P_PRODUCT_DEFAULT => $this->getDefaultProduct(),
                self::P_PRODUCT_QUANTITY => $this->getDefaultQuantity(),
                self::P_PRODUCT_EDIT => $this->isDefaultEdit(),
            ]
        );
        foreach (self::PASSWORD_OPTIONS as $option) {
            $properties[$option] = $this->getPropertyBoolean($option);
        }

        return $properties;
    }

    /**
     * Gets the password strength level.
     */
    public function getStrengthLevel(): StrengthLevel
    {
        return $this->getPropertyEnum(self::P_STRENGTH_LEVEL, self::DEFAULT_STRENGTH_LEVEL);
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
        $role = $this->builder->getRoleUser();
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
     */
    public function isDefaultEdit(): bool
    {
        return $this->getPropertyBoolean(self::P_PRODUCT_EDIT, self::DEFAULT_PRODUCT_EDIT);
    }

    /**
     * Gets a value indicating the image captcha is displayed when login.
     */
    public function isDisplayCaptcha(): bool
    {
        return $this->getPropertyBoolean(self::P_DISPLAY_CAPTCHA, !$this->debug);
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
        }

        return !$this->isFloatZero($value) && $value < $this->getMinMargin();
    }

    public function isMessageClose(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_CLOSE, self::DEFAULT_MESSAGE_CLOSE);
    }

    public function isMessageIcon(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_ICON, self::DEFAULT_MESSAGE_ICON);
    }

    public function isMessageSubTitle(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_SUB_TITLE, self::DEFAULT_MESSAGE_SUB_TITLE);
    }

    public function isMessageTitle(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_TITLE, self::DEFAULT_MESSAGE_TITLE);
    }

    public function isPanelCatalog(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_CATALOG, self::DEFAULT_TRUE);
    }

    public function isPanelMonth(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_MONTH, self::DEFAULT_TRUE);
    }

    public function isPanelState(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_STATE, self::DEFAULT_TRUE);
    }

    public function isPrintAddress(): bool
    {
        return $this->getPropertyBoolean(self::P_PRINT_ADDRESS, self::DEFAULT_PRINT_ADDRESS);
    }

    public function isQrCode(): bool
    {
        return $this->getPropertyBoolean(self::P_QR_CODE, self::DEFAULT_QR_CODE);
    }

    public function isStatusBar(): bool
    {
        return $this->getPropertyBoolean(self::P_STATUS_BAR, self::DEFAULT_TRUE);
    }

    /**
     * Remove the give property.
     */
    public function removeProperty(string $name): self
    {
        /** @psalm-var PropertyRepository $repository */
        $repository = $this->manager->getRepository(Property::class);
        $property = $repository->findOneByName($name);
        if ($property instanceof Property) {
            $repository->remove($property);
            $this->updateAdapter();
        }

        return $this;
    }

    /**
     * Set the date of the last archive calculations.
     */
    public function setLastArchiveCalculations(\DateTimeInterface $date = new \DateTime()): self
    {
        return $this->setProperty(PropertyServiceInterface::P_DATE_CALCULATION, $date);
    }

    /**
     * Set the date of the last update calculations.
     */
    public function setLastUpdateCalculations(\DateTimeInterface $date = new \DateTime()): self
    {
        return $this->setProperty(PropertyServiceInterface::P_UPDATE_CALCULATION, $date);
    }

    /**
     * Set the date of the last update of product prices.
     */
    public function setLastUpdateProducts(\DateTimeInterface $date = new \DateTime()): self
    {
        return $this->setProperty(PropertyServiceInterface::P_DATE_PRODUCT, $date);
    }

    /**
     * @param array<string, mixed> $properties
     */
    public function setProperties(array $properties): static
    {
        if ([] === $properties) {
            return $this;
        }

        /** @psalm-var PropertyRepository $repository */
        $repository = $this->manager->getRepository(Property::class);
        $defaultValues = $this->getDefaultValues();
        /** @psalm-var mixed $value */
        foreach ($properties as $key => $value) {
            $this->saveProperty($key, $value, $defaultValues, $repository);
        }
        $repository->flush();
        $this->updateAdapter();

        return $this;
    }

    /**
     * Remove the default category if deleted from the database.
     */
    public function updateDeletedCategory(Category $category): void
    {
        $this->updateDeletedEntity(self::P_DEFAULT_CATEGORY, $category);
    }

    /**
     * Remove the default product if deleted from the database.
     */
    public function updateDeletedProduct(Product $product): void
    {
        $this->updateDeletedEntity(self::P_PRODUCT_DEFAULT, $product);
    }

    /**
     * Remove the default calculation state if deleted from the database.
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
     * @template T of AbstractEntity
     *
     * @psalm-param class-string<T> $entityName
     *
     * @psalm-return T|null
     */
    private function findEntity(string $propertyName, string $entityName): ?AbstractEntity
    {
        $id = $this->getPropertyInteger($propertyName);
        if (0 === $id) {
            return null;
        }

        return $this->manager->getRepository($entityName)->find($id);
    }

    /**
     * Update a property without saving changes to database.
     *
     * @param array<string, mixed> $defaultValues
     */
    private function saveProperty(string $name, mixed $value, array $defaultValues, PropertyRepository $repository): void
    {
        $property = $repository->findOneByName($name);
        if ($this->isDefaultValue($defaultValues, $name, $value)) {
            if ($property instanceof Property) {
                $repository->remove($property, false);
            }
        } else {
            if (!$property instanceof Property) {
                $property = Property::instance($name);
                $repository->add($property, false);
            }
            $property->setValue($value);
        }
    }

    private function updateDeletedEntity(string $name, AbstractEntity $entity): void
    {
        if ($this->getPropertyInteger($name) === $entity->getId()) {
            $this->setProperty($name, null);
        }
    }
}
