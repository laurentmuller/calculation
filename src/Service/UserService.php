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

use App\Entity\User;
use App\Entity\UserProperty;
use App\Enums\EntityAction;
use App\Enums\MessagePosition;
use App\Enums\TableView;
use App\Interfaces\PropertyServiceInterface;
use App\Model\CustomerInformation;
use App\Repository\UserPropertyRepository;
use App\Traits\PropertyTrait;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to manage user properties.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class UserService implements PropertyServiceInterface, ServiceSubscriberInterface
{
    use PropertyTrait;

    public function __construct(
        private readonly ApplicationService $service,
        private readonly UserPropertyRepository $repository,
        private readonly Security $security,
        #[Target('user_cache')]
        CacheItemPoolInterface $cache
    ) {
        $this->setAdapter($cache);
    }

    /**
     * Gets the application service.
     */
    public function getApplication(): ApplicationService
    {
        return $this->service;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCustomer(): CustomerInformation
    {
        $customer = $this->service->getCustomer();
        $customer->setPrintAddress($this->isPrintAddress());

        return $customer;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDisplayMode(): TableView
    {
        $default = $this->service->getDisplayMode();
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
        $default = $this->service->getEditAction();
        $value = $this->getPropertyString(self::P_EDIT_ACTION, $default->value);

        return EntityAction::tryFrom((string) $value) ?? $default;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMessagePosition(): MessagePosition
    {
        $default = $this->service->getMessagePosition();
        $value = (string) $this->getPropertyString(self::P_MESSAGE_POSITION, $default->value);

        return MessagePosition::tryFrom($value) ?? $default;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMessageProgress(): int
    {
        $default = $this->service->getMessageProgress();

        return $this->getPropertyInteger(self::P_MESSAGE_PROGRESS, $default);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMessageTimeout(): int
    {
        $default = $this->service->getMessageTimeout();

        return $this->getPropertyInteger(self::P_MESSAGE_TIMEOUT, $default);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getPanelCalculation(): int
    {
        $default = $this->service->getPanelCalculation();

        return $this->getPropertyInteger(self::P_PANEL_CALCULATION, $default);
    }

    /**
     * Gets all properties.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getProperties(): array
    {
        // reload data
        $this->updateAdapter();

        return [
            // display
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
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageClose(): bool
    {
        $default = $this->service->isMessageClose();

        return $this->isPropertyBoolean(self::P_MESSAGE_CLOSE, $default);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageIcon(): bool
    {
        $default = $this->service->isMessageIcon();

        return $this->isPropertyBoolean(self::P_MESSAGE_ICON, $default);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageSubTitle(): bool
    {
        $default = $this->service->isMessageSubTitle();

        return $this->isPropertyBoolean(self::P_MESSAGE_SUB_TITLE, $default);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageTitle(): bool
    {
        $default = $this->service->isMessageTitle();

        return $this->isPropertyBoolean(self::P_MESSAGE_TITLE, $default);
    }

    /**
     * Returns a value indicating if the catalog panel is displayed in the home page.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPanelCatalog(): bool
    {
        $default = $this->service->isPanelCatalog();

        return $this->isPropertyBoolean(self::P_PANEL_CATALOG, $default);
    }

    /**
     * Returns a value indicating if the month panel is displayed in the home page.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPanelMonth(): bool
    {
        $default = $this->service->isPanelMonth();

        return $this->isPropertyBoolean(self::P_PANEL_MONTH, $default);
    }

    /**
     * Returns a value indicating if the state panel is displayed in the home page.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPanelState(): bool
    {
        $default = $this->service->isPanelState();

        return $this->isPropertyBoolean(self::P_PANEL_STATE, $default);
    }

    /**
     * Gets a value indicating if the customer address is output within the PDF documents.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPrintAddress(): bool
    {
        $default = $this->service->isPrintAddress();

        return $this->isPropertyBoolean(self::P_PRINT_ADDRESS, $default);
    }

    /**
     * Gets a value indicating if a QR-Code is output at the end of the PDF documents.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isQrCode(): bool
    {
        $default = $this->service->isQrCode();

        return $this->isPropertyBoolean(self::P_QR_CODE, $default);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isStatusBar(): bool
    {
        $default = $this->service->isStatusBar();

        return $this->isPropertyBoolean(self::P_STATUS_BAR, $default);
    }

    /**
     * Save the given properties to the database and to the cache.
     *
     * @param array<string, mixed> $properties the properties to set
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function setProperties(array $properties): self
    {
        if (!empty($properties) && null !== $user = $this->getUser()) {
            $defaultProperties = $this->service->getProperties();

            /** @psalm-var mixed $value */
            foreach ($properties as $key => $value) {
                $this->saveProperty($defaultProperties, $user, $key, $value);
            }
            $this->repository->flush();
            $this->updateAdapter();
        }

        return $this;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function updateAdapter(): void
    {
        if (null !== $user = $this->getUser()) {
            $properties = $this->repository->findByUser($user);
            $this->saveProperties($properties);
        }
    }

    private function getUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
    }

    /**
     * Update a property without saving changes.
     */
    private function saveProperty(array $defaultProperties, User $user, string $name, mixed $value): void
    {
        $property = $this->repository->findByName($user, $name);
        if ($this->isDefaultValue($defaultProperties, $name, $value)) {
            if ($property instanceof UserProperty) {
                $this->repository->remove($property, false);
            }

            return;
        }
        if (!$property instanceof UserProperty) {
            $property = new UserProperty($name);
            $property->setUser($user);
            $this->repository->add($property, false);
        }
        $property->setValue($value);
    }
}
