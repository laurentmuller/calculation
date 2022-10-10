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
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to manage user properties.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class UserService implements PropertyServiceInterface, ServiceSubscriberInterface
{
    use PropertyTrait;

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __construct(
        private readonly UserPropertyRepository $repository,
        private readonly ApplicationService $service,
        private readonly Security $security,
        #[Target('user_cache')]
        CacheItemPoolInterface $cache
    ) {
        $this->setAdapter($cache);
        $this->updateAdapter();
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
     * Gets the message attributes.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMessageAttributes(): array
    {
        return [
            'icon' => $this->isMessageIcon(),
            'title' => $this->isMessageTitle(),
            'display-close' => $this->isMessageClose(),
            'display-subtitle' => $this->isMessageSubTitle(),
            'timeout' => $this->getMessageTimeout(),
            'progress' => $this->getMessageProgress(),
            'position' => $this->getMessagePosition()->value,
        ];
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
        return $this->getPropertyInteger(self::P_MESSAGE_PROGRESS, $this->service->getMessageProgress());
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMessageTimeout(): int
    {
        return $this->getPropertyInteger(self::P_MESSAGE_TIMEOUT, $this->service->getMessageTimeout());
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getPanelCalculation(): int
    {
        return $this->getPropertyInteger(self::P_PANEL_CALCULATION, $this->service->getPanelCalculation());
    }

    /**
     * Gets all properties.
     *
     * @return array<string, mixed>
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getProperties(): array
    {
        return $this->loadProperties();
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageClose(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_CLOSE, $this->service->isMessageClose());
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageIcon(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_ICON, $this->service->isMessageIcon());
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageSubTitle(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_SUB_TITLE, $this->service->isMessageSubTitle());
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageTitle(): bool
    {
        return $this->isPropertyBoolean(self::P_MESSAGE_TITLE, $this->service->isMessageTitle());
    }

    /**
     * Returns a value indicating if the catalog panel is displayed in the home page.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPanelCatalog(): bool
    {
        return $this->isPropertyBoolean(self::P_PANEL_CATALOG, $this->service->isPanelCatalog());
    }

    /**
     * Returns a value indicating if the month panel is displayed in the home page.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPanelMonth(): bool
    {
        return $this->isPropertyBoolean(self::P_PANEL_MONTH, $this->service->isPanelMonth());
    }

    /**
     * Returns a value indicating if the state panel is displayed in the home page.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPanelState(): bool
    {
        return $this->isPropertyBoolean(self::P_PANEL_STATE, $this->service->isPanelState());
    }

    /**
     * Gets a value indicating if the customer address is output within the PDF documents.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPrintAddress(): bool
    {
        return $this->isPropertyBoolean(self::P_PRINT_ADDRESS, $this->service->isPrintAddress());
    }

    /**
     * Gets a value indicating if a QR-Code is output at the end of the PDF documents.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isQrCode(): bool
    {
        return $this->isPropertyBoolean(self::P_QR_CODE, $this->service->isQrCode());
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isStatusBar(): bool
    {
        return $this->isPropertyBoolean(self::P_STATUS_BAR, $this->service->isStatusBar());
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
            $defaultValues = $this->service->getProperties();

            /** @psalm-var mixed $value */
            foreach ($properties as $key => $value) {
                $this->saveProperty($key, $value, $defaultValues, $user);
            }
            $this->repository->flush();
            $this->updateAdapter();
        }

        return $this;
    }

    private function getUser(): ?UserInterface
    {
        return $this->security->getUser();
    }

    /**
     * Update a property without saving changes to database.
     */
    private function saveProperty(string $name, mixed $value, array $defaultValues, UserInterface $user): void
    {
        $property = $this->repository->findOneByUserAndName($user, $name);
        if ($this->isDefaultValue($defaultValues, $name, $value)) {
            // remove if present
            if ($property instanceof UserProperty) {
                $this->repository->remove($property, false);
            }
        } else {
            // create if needed
            if (!$property instanceof UserProperty) {
                $property = UserProperty::instance($name, $user);
                $this->repository->add($property, false);
            }
            $property->setValue($value);
        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function updateAdapter(): void
    {
        if (null !== $user = $this->getUser()) {
            $properties = $this->repository->findByUser($user);
            $this->saveProperties($properties);
        }
    }
}
