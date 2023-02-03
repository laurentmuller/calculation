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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Target;
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

    public function __construct(
        private readonly UserPropertyRepository $repository,
        private readonly ApplicationService $service,
        private readonly Security $security,
        #[Target('user_cache')]
        CacheItemPoolInterface $cache
    ) {
        $this->setCacheAdapter($cache);
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
     */
    public function getCustomer(): CustomerInformation
    {
        $customer = $this->service->getCustomer();
        $customer->setPrintAddress($this->isPrintAddress());

        return $customer;
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayMode(): TableView
    {
        $default = $this->service->getDisplayMode();
        $value = $this->getPropertyString(self::P_DISPLAY_MODE, $default->value);

        return TableView::tryFrom($value) ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function getEditAction(): EntityAction
    {
        $default = $this->service->getEditAction();
        $value = $this->getPropertyString(self::P_EDIT_ACTION, $default->value);

        return EntityAction::tryFrom($value) ?? $default;
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
     */
    public function getMessagePosition(): MessagePosition
    {
        $default = $this->service->getMessagePosition();
        $value = $this->getPropertyString(self::P_MESSAGE_POSITION, $default->value);

        return MessagePosition::tryFrom($value) ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function getMessageProgress(): int
    {
        return $this->getPropertyInteger(self::P_MESSAGE_PROGRESS, $this->service->getMessageProgress());
    }

    /**
     * {@inheritDoc}
     */
    public function getMessageTimeout(): int
    {
        return $this->getPropertyInteger(self::P_MESSAGE_TIMEOUT, $this->service->getMessageTimeout());
    }

    /**
     * {@inheritDoc}
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
     */
    public function isMessageClose(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_CLOSE, $this->service->isMessageClose());
    }

    /**
     * {@inheritDoc}
     */
    public function isMessageIcon(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_ICON, $this->service->isMessageIcon());
    }

    /**
     * {@inheritDoc}
     */
    public function isMessageSubTitle(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_SUB_TITLE, $this->service->isMessageSubTitle());
    }

    /**
     * {@inheritDoc}
     */
    public function isMessageTitle(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_TITLE, $this->service->isMessageTitle());
    }

    /**
     * {@inheritDoc}
     */
    public function isPanelCatalog(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_CATALOG, $this->service->isPanelCatalog());
    }

    /**
     * {@inheritDoc}
     */
    public function isPanelMonth(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_MONTH, $this->service->isPanelMonth());
    }

    /**
     * {@inheritDoc}
     */
    public function isPanelState(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_STATE, $this->service->isPanelState());
    }

    /**
     * {@inheritDoc}
     */
    public function isPrintAddress(): bool
    {
        return $this->getPropertyBoolean(self::P_PRINT_ADDRESS, $this->service->isPrintAddress());
    }

    /**
     * {@inheritDoc}
     */
    public function isQrCode(): bool
    {
        return $this->getPropertyBoolean(self::P_QR_CODE, $this->service->isQrCode());
    }

    /**
     * {@inheritDoc}
     */
    public function isStatusBar(): bool
    {
        return $this->getPropertyBoolean(self::P_STATUS_BAR, $this->service->isStatusBar());
    }

    public function setProperties(array $properties): static
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

    protected function updateAdapter(): void
    {
        if (null !== $user = $this->getUser()) {
            $properties = $this->repository->findByUser($user);
            $this->saveProperties($properties);
        }
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
}
