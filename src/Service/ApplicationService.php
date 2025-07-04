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

use App\Constraint\Password;
use App\Constraint\Strength;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\GlobalProperty;
use App\Entity\Product;
use App\Enums\EntityAction;
use App\Enums\MessagePosition;
use App\Enums\StrengthLevel;
use App\Enums\TableView;
use App\Interfaces\EntityInterface;
use App\Interfaces\PropertyServiceInterface;
use App\Model\CustomerInformation;
use App\Model\Role;
use App\Repository\GlobalPropertyRepository;
use App\Traits\ArrayTrait;
use App\Traits\MathTrait;
use App\Traits\PropertyServiceTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;

/**
 * Service to manage application properties.
 */
class ApplicationService implements PropertyServiceInterface
{
    use ArrayTrait;
    use MathTrait;

    /** @use PropertyServiceTrait<GlobalProperty> */
    use PropertyServiceTrait;

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly RoleBuilderService $builder,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
        #[Target('calculation.application')]
        protected readonly CacheItemPoolInterface $cacheItemPool
    ) {
        $this->initialize();
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

    #[\Override]
    public function getCalculations(): int
    {
        return $this->getPropertyInteger(self::P_CALCULATIONS, self::DEFAULT_CALCULATIONS);
    }

    #[\Override]
    public function getCustomer(): CustomerInformation
    {
        $info = new CustomerInformation();
        $info->setName($this->getCustomerName())
            ->setAddress($this->getCustomerAddress())
            ->setZipCity($this->getCustomerZipCity())
            ->setPhone($this->getCustomerPhone())
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
            self::P_PRODUCT_EDIT => self::DEFAULT_FALSE,
            self::P_PRODUCT_QUANTITY => 0,
            // display and edit entities
            self::P_DISPLAY_MODE => self::DEFAULT_DISPLAY_MODE,
            self::P_EDIT_ACTION => self::DEFAULT_ACTION,
            // notification
            self::P_MESSAGE_POSITION => self::DEFAULT_MESSAGE_POSITION,
            self::P_MESSAGE_TIMEOUT => self::DEFAULT_MESSAGE_TIMEOUT,
            self::P_MESSAGE_TITLE => self::DEFAULT_TRUE,
            self::P_MESSAGE_SUB_TITLE => self::DEFAULT_FALSE,
            self::P_MESSAGE_PROGRESS => self::DEFAULT_MESSAGE_PROGRESS,
            self::P_MESSAGE_ICON => self::DEFAULT_TRUE,
            self::P_MESSAGE_CLOSE => self::DEFAULT_TRUE,
            // home page
            self::P_CALCULATIONS => self::DEFAULT_CALCULATIONS,
            self::P_PANEL_CATALOG => self::DEFAULT_TRUE,
            self::P_PANEL_STATE => self::DEFAULT_TRUE,
            self::P_PANEL_MONTH => self::DEFAULT_TRUE,
            self::P_STATUS_BAR => self::DEFAULT_TRUE,
            self::P_DARK_NAVIGATION => self::DEFAULT_TRUE,
            // document's options
            self::P_QR_CODE => self::DEFAULT_FALSE,
            self::P_PRINT_ADDRESS => self::DEFAULT_FALSE,
            // security
            self::P_STRENGTH_LEVEL => StrengthLevel::NONE,
            self::P_DISPLAY_CAPTCHA => !$this->debug,
            self::P_COMPROMISED_PASSWORD => self::DEFAULT_FALSE,
        ];

        // password options
        $password = new Password();
        foreach (self::PASSWORD_OPTIONS as $key => $option) {
            $properties[$key] = $password->isOption($option);
        }

        return $properties;
    }

    #[\Override]
    public function getDisplayMode(): TableView
    {
        return $this->getPropertyEnum(self::P_DISPLAY_MODE, self::DEFAULT_DISPLAY_MODE);
    }

    #[\Override]
    public function getEditAction(): EntityAction
    {
        return $this->getPropertyEnum(self::P_EDIT_ACTION, self::DEFAULT_ACTION);
    }

    /**
     * Gets the last archive calculations date.
     */
    public function getLastArchiveCalculations(): ?DatePoint
    {
        return $this->getPropertyDate(self::P_DATE_CALCULATION);
    }

    /**
     * Gets the last import of Swiss cities.
     */
    public function getLastImport(): ?DatePoint
    {
        return $this->getPropertyDate(self::P_DATE_IMPORT);
    }

    /**
     * Gets the last update calculations date.
     */
    public function getLastUpdateCalculations(): ?DatePoint
    {
        return $this->getPropertyDate(self::P_UPDATE_CALCULATION);
    }

    /**
     * Gets the last products update.
     */
    public function getLastUpdateProducts(): ?DatePoint
    {
        return $this->getPropertyDate(self::P_DATE_PRODUCT);
    }

    #[\Override]
    public function getMessagePosition(): MessagePosition
    {
        return $this->getPropertyEnum(self::P_MESSAGE_POSITION, self::DEFAULT_MESSAGE_POSITION);
    }

    #[\Override]
    public function getMessageProgress(): int
    {
        return $this->getPropertyInteger(self::P_MESSAGE_PROGRESS, self::DEFAULT_MESSAGE_PROGRESS);
    }

    #[\Override]
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
     * Gets the password constraint.
     */
    public function getPasswordConstraint(): Password
    {
        $contraint = new Password();
        foreach (PropertyServiceInterface::PASSWORD_OPTIONS as $property => $option) {
            $contraint->setOption($option, $this->getPropertyBoolean($property));
        }

        return $contraint;
    }

    /**
     * Gets all properties except date values.
     *
     * @return array<string, mixed>
     */
    public function getProperties(bool $updateAdapter = true): array
    {
        $properties = \array_merge(
            $this->loadProperties($updateAdapter),
            [
                // customer
                self::P_CUSTOMER_NAME => $this->getCustomerName(),
                self::P_CUSTOMER_ADDRESS => $this->getCustomerAddress(),
                self::P_CUSTOMER_ZIP_CITY => $this->getCustomerZipCity(),
                self::P_CUSTOMER_PHONE => $this->getCustomerPhone(),
                self::P_CUSTOMER_EMAIL => $this->getCustomerEmail(),
                self::P_CUSTOMER_URL => $this->getCustomerUrl(),
                // security
                self::P_DISPLAY_CAPTCHA => $this->isDisplayCaptcha(),
                self::P_STRENGTH_LEVEL => $this->getStrengthLevel(),
                self::P_COMPROMISED_PASSWORD => $this->isCompromisedPassword(),
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
        foreach (\array_keys(self::PASSWORD_OPTIONS) as $property) {
            $properties[$property] = $this->getPropertyBoolean($property);
        }

        return $properties;
    }

    public function getStrengthConstraint(): Strength
    {
        return new Strength($this->getStrengthLevel());
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
     * Returns a value indicating if to check for compromised password.
     */
    public function isCompromisedPassword(): bool
    {
        return $this->getPropertyBoolean(self::P_COMPROMISED_PASSWORD);
    }

    #[\Override]
    public function isConnected(): bool
    {
        return $this->manager->getConnection()->isConnected();
    }

    /**
     * Return the debug state.
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Gets a value indicating if the default product (if any) must be edited
     * when a new calculation is created.
     */
    public function isDefaultEdit(): bool
    {
        return $this->getPropertyBoolean(self::P_PRODUCT_EDIT, self::DEFAULT_FALSE);
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

    #[\Override]
    public function isMessageClose(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_CLOSE, self::DEFAULT_TRUE);
    }

    #[\Override]
    public function isMessageIcon(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_ICON, self::DEFAULT_TRUE);
    }

    #[\Override]
    public function isMessageSubTitle(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_SUB_TITLE, self::DEFAULT_FALSE);
    }

    #[\Override]
    public function isMessageTitle(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_TITLE, self::DEFAULT_TRUE);
    }

    #[\Override]
    public function isPanelCatalog(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_CATALOG, self::DEFAULT_TRUE);
    }

    #[\Override]
    public function isPanelMonth(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_MONTH, self::DEFAULT_TRUE);
    }

    #[\Override]
    public function isPanelState(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_STATE, self::DEFAULT_TRUE);
    }

    #[\Override]
    public function isPrintAddress(): bool
    {
        return $this->getPropertyBoolean(self::P_PRINT_ADDRESS, self::DEFAULT_FALSE);
    }

    #[\Override]
    public function isQrCode(): bool
    {
        return $this->getPropertyBoolean(self::P_QR_CODE, self::DEFAULT_FALSE);
    }

    #[\Override]
    public function isStatusBar(): bool
    {
        return $this->getPropertyBoolean(self::P_STATUS_BAR, self::DEFAULT_TRUE);
    }

    /**
     * Remove the give property.
     */
    public function removeProperty(string $name): bool
    {
        $repository = $this->getPropertyRepository();
        $property = $repository->findOneByName($name);
        if ($property instanceof GlobalProperty) {
            $repository->remove($property);
            $this->updateAdapter();

            return true;
        }

        return false;
    }

    /**
     * Set the date of the last archive calculations.
     */
    public function setLastArchiveCalculations(DatePoint $date = new DatePoint()): bool
    {
        return $this->setProperty(PropertyServiceInterface::P_DATE_CALCULATION, $date);
    }

    /**
     * Set the date of the last update calculations.
     */
    public function setLastUpdateCalculations(DatePoint $date = new DatePoint()): bool
    {
        return $this->setProperty(PropertyServiceInterface::P_UPDATE_CALCULATION, $date);
    }

    /**
     * Set the date of the last update of product prices.
     */
    public function setLastUpdateProducts(DatePoint $date = new DatePoint()): bool
    {
        return $this->setProperty(PropertyServiceInterface::P_DATE_PRODUCT, $date);
    }

    /**
     * @param array<string, mixed> $properties the properties to set
     *
     * @return bool true if one or more properties have changed
     */
    #[\Override]
    public function setProperties(array $properties): bool
    {
        if ([] === $properties) {
            return false;
        }
        if (!$this->isPropertiesChanged($properties)) {
            return false;
        }

        $defaultValues = $this->getDefaultValues();
        $repository = $this->getPropertyRepository();
        $existingProperties = $this->getExistingProperties();

        /** @phpstan-var mixed $value */
        foreach ($properties as $key => $value) {
            $this->saveProperty($key, $value, $defaultValues, $existingProperties, $repository);
        }
        $this->manager->flush();
        $this->updateAdapter();

        return true;
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

    /**
     * @return GlobalProperty[]
     */
    #[\Override]
    protected function loadEntities(): array
    {
        return $this->getPropertyRepository()->findAll();
    }

    /**
     * @template TEntity of EntityInterface
     *
     * @phpstan-param class-string<TEntity> $entityName
     *
     * @phpstan-return TEntity|null
     */
    private function findEntity(string $propertyName, string $entityName): ?EntityInterface
    {
        $id = $this->getPropertyInteger($propertyName);
        if (0 === $id) {
            return null;
        }

        return $this->manager->getRepository($entityName)
            ->find($id);
    }

    /**
     * @return array<string, GlobalProperty>
     */
    private function getExistingProperties(): array
    {
        return $this->mapToKeyValue(
            $this->loadEntities(),
            fn (GlobalProperty $property): array => [$property->getName() => $property],
        );
    }

    private function getPropertyRepository(): GlobalPropertyRepository
    {
        return $this->manager->getRepository(GlobalProperty::class);
    }

    /**
     * @param array<string, mixed>          $defaultValues
     * @param array<string, GlobalProperty> $existingProperties
     */
    private function saveProperty(
        string $name,
        mixed $value,
        array $defaultValues,
        array $existingProperties,
        GlobalPropertyRepository $repository
    ): void {
        if ($this->isDefaultValue($defaultValues, $name, $value)) {
            if (isset($existingProperties[$name])) {
                $repository->remove($existingProperties[$name], false);
            }
        } else {
            $property = $existingProperties[$name] ?? GlobalProperty::instance($name);
            $property->setValue($value);
            $repository->persist($property, false);
        }
    }

    private function updateDeletedEntity(string $name, EntityInterface $entity): void
    {
        if ($this->getPropertyInteger($name) === $entity->getId()) {
            $this->setProperty($name, null);
        }
    }
}
