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

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Property;
use App\Enums\EntityAction;
use App\Enums\MessagePosition;
use App\Enums\TableView;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\StrengthInterface;
use App\Model\CustomerInformation;
use App\Model\Role;
use App\Repository\PropertyRepository;
use App\Security\EntityVoter;
use App\Traits\PropertyTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to manage application properties.
 */
class ApplicationService extends AppVariable implements LoggerAwareInterface, PropertyServiceInterface
{
    use PropertyTrait;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $manager,
        LoggerInterface $logger,
        KernelInterface $kernel,
        TranslatorInterface $translator,
        CacheItemPoolInterface $applicationCache
    ) {
        $this->setLogger($logger);
        $this->translator = $translator;
        $this->setDebug($kernel->isDebug());
        $this->setAdapter($applicationCache);
        $this->setEnvironment($kernel->getEnvironment());
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
        $role = EntityVoter::getRoleAdmin();
        /** @psalm-var int[] $rights */
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
        return $this->getPropertyDate(self::P_ARCHIVE_CALCULATION);
    }

    /**
     * Gets the customer information.
     *
     * @throws \Psr\Cache\InvalidArgumentException
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
        return [
            self::P_MIN_MARGIN => self::DEFAULT_MIN_MARGIN,

            self::P_DISPLAY_MODE => self::DEFAULT_DISPLAY_MODE,
            self::P_EDIT_ACTION => self::DEFAULT_ACTION,

            self::P_MESSAGE_POSITION => self::DEFAULT_MESSAGE_POSITION,
            self::P_MESSAGE_TIMEOUT => self::DEFAULT_MESSAGE_TIMEOUT,
            self::P_MESSAGE_TITLE => self::DEFAULT_MESSAGE_TITLE,
            self::P_MESSAGE_SUB_TITLE => self::DEFAULT_MESSAGE_SUB_TITLE,
            self::P_MESSAGE_PROGRESS => self::DEFAULT_MESSAGE_PROGRESS,
            self::P_MESSAGE_ICON => self::DEFAULT_MESSAGE_ICON,
            self::P_MESSAGE_CLOSE => self::DEFAULT_MESSAGE_CLOSE,

            self::P_PANEL_STATE => true,
            self::P_PANEL_MONTH => true,
            self::P_PANEL_CATALOG => true,
            self::P_PANEL_CALCULATION => self::DEFAULT_PANEL_CALCULATION,

            self::P_QR_CODE => self::DEFAULT_QR_CODE,
            self::P_PRINT_ADDRESS => self::DEFAULT_PRINT_ADDRESS,

            self::P_DEFAULT_PRODUCT_EDIT => true,
            self::P_DEFAULT_PRODUCT_QUANTITY => 0,

            self::P_MIN_STRENGTH => StrengthInterface::LEVEL_NONE,
            self::P_DISPLAY_CAPTCHA => !$this->getDebug(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDisplayMode(): TableView
    {
        $default = self::DEFAULT_DISPLAY_MODE;
        $value = (string) $this->getPropertyString(self::P_DISPLAY_MODE, $default->value);

        return TableView::tryFrom($value) ?? $default;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
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
        return $this->getPropertyDate(self::P_LAST_IMPORT);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMessagePosition(): MessagePosition
    {
        $default = self::DEFAULT_MESSAGE_POSITION;
        $value = (string) $this->getPropertyString(self::P_MESSAGE_POSITION, $default->value);

        return MessagePosition::tryFrom($value) ?? $default;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
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
     * Gets the minimum password strength.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMinStrength(): int
    {
        return $this->getPropertyInteger(self::P_MIN_STRENGTH, StrengthInterface::LEVEL_NONE);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getPanelCalculation(): int
    {
        return $this->getPropertyInteger(self::P_PANEL_CALCULATION, self::DEFAULT_PANEL_CALCULATION);
    }

    /**
     * Gets all properties.
     *
     * @param string[] $excluded the property keys to exclude
     *
     * @return array<string, mixed>
     *
     * @throws \Psr\Cache\InvalidArgumentException
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

            self::P_ARCHIVE_CALCULATION => $this->getArchiveCalculation(),
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

        // exclude keys
        if (!empty($excluded)) {
            return \array_diff_key($result, \array_flip($excluded));
        }

        return $result;
    }

    /**
     * Gets the last products update.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getUpdateProducts(): ?\DateTimeInterface
    {
        return $this->getPropertyDate(self::P_UPDATE_PRODUCTS);
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
        $role = EntityVoter::getRoleUser();
        /** @psalm-var int[] $rights */
        $rights = $this->getPropertyArray(self::P_USER_RIGHTS, $role->getRights());
        $role->setRights($rights);

        return $role;
    }

    /**
     * Gets a value indicating if the default product (if any)  must be edited
     * when a new calculation is created.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isDefaultEdit(): bool
    {
        return $this->isPropertyBoolean(self::P_DEFAULT_PRODUCT_EDIT, self::DEFAULT_PRODUCT_EDIT);
    }

    /**
     * Gets a value indicating the image captcha is displayed when login.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isDisplayCaptcha(): bool
    {
        return $this->isPropertyBoolean(self::P_DISPLAY_CAPTCHA, !$this->getDebug());
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
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageClose(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_CLOSE, self::DEFAULT_MESSAGE_CLOSE);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageIcon(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_ICON, self::DEFAULT_MESSAGE_ICON);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageProgress(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_PROGRESS, self::DEFAULT_MESSAGE_PROGRESS);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageSubTitle(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_SUB_TITLE, self::DEFAULT_MESSAGE_SUB_TITLE);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageTitle(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_TITLE, self::DEFAULT_MESSAGE_TITLE);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPanelCatalog(): bool
    {
        return $this->isPropertyBoolean(self::P_PANEL_CATALOG, true);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPanelMonth(): bool
    {
        return $this->isPropertyBoolean(self::P_PANEL_MONTH, true);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPanelState(): bool
    {
        return $this->isPropertyBoolean(self::P_PANEL_STATE, true);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPrintAddress(): bool
    {
        return $this->isPropertyBoolean(self::P_PRINT_ADDRESS, self::DEFAULT_PRINT_ADDRESS);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isQrCode(): bool
    {
        return $this->isPropertyBoolean(self::P_QR_CODE, self::DEFAULT_QR_CODE);
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

    /**
     * Save the given properties to the database and to the cache.
     *
     * @param array<string, mixed> $properties        the properties to set
     * @param array<string, mixed> $defaultProperties the default properties
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function setProperties(array $properties, ?array $defaultProperties = null): self
    {
        if (!empty($properties)) {
            $repository = $this->getRepository();
            $defaultProperties ??= $this->getDefaultValues();

            /** @psalm-var mixed $value */
            foreach ($properties as $key => $value) {
                $this->saveProperty($repository, $defaultProperties, $key, $value);
            }
            $this->manager->flush();
            $this->updateAdapter();
        }

        return $this;
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
     * Update a property without saving changes.
     */
    private function saveProperty(PropertyRepository $repository, array $defaultProperties, string $name, mixed $value): void
    {
        $property = $repository->findOneByName($name);
        if ($this->isDefaultValue($defaultProperties, $name, $value)) {
            if ($property instanceof Property) {
                $repository->remove($property, false);
            }

            return;
        }
        if (!$property instanceof Property) {
            $property = new Property($name);
            $repository->add($property, false);
        }
        $property->setValue($value);
    }

    /**
     * Update the content of the cache from the repository.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function updateAdapter(): void
    {
        $properties = $this->manager->getRepository(Property::class)->findAll();
        $this->saveProperties($properties);
    }
}
