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
use App\Traits\ArrayTrait;
use App\Traits\PropertyServiceTrait;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Service to manage user properties.
 */
class UserService implements PropertyServiceInterface
{
    use ArrayTrait;
    /** @use PropertyServiceTrait<UserProperty> */
    use PropertyServiceTrait;

    public function __construct(
        private readonly UserPropertyRepository $repository,
        private readonly ApplicationService $service,
        private readonly Security $security,
        #[Target('calculation.user')]
        protected readonly CacheItemPoolInterface $cacheItemPool
    ) {
        $this->initialize();
    }

    /**
     * Gets the application service.
     */
    public function getApplication(): ApplicationService
    {
        return $this->service;
    }

    #[\Override]
    public function getCalculations(): int
    {
        return $this->getPropertyInteger(self::P_CALCULATIONS, $this->service->getCalculations());
    }

    #[\Override]
    public function getCustomer(): CustomerInformation
    {
        $customer = $this->service->getCustomer();
        $customer->setPrintAddress($this->isPrintAddress());

        return $customer;
    }

    #[\Override]
    public function getDisplayMode(): TableView
    {
        return $this->getPropertyEnum(self::P_DISPLAY_MODE, $this->service->getDisplayMode());
    }

    #[\Override]
    public function getEditAction(): EntityAction
    {
        return $this->getPropertyEnum(self::P_EDIT_ACTION, $this->service->getEditAction());
    }

    /**
     * Gets the message attributes.
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

    #[\Override]
    public function getMessagePosition(): MessagePosition
    {
        return $this->getPropertyEnum(self::P_MESSAGE_POSITION, $this->service->getMessagePosition());
    }

    #[\Override]
    public function getMessageProgress(): int
    {
        return $this->getPropertyInteger(self::P_MESSAGE_PROGRESS, $this->service->getMessageProgress());
    }

    #[\Override]
    public function getMessageTimeout(): int
    {
        return $this->getPropertyInteger(self::P_MESSAGE_TIMEOUT, $this->service->getMessageTimeout());
    }

    /**
     * Gets all properties.
     *
     * @return array<string, mixed>
     */
    public function getProperties(bool $updateAdapter = true): array
    {
        return $this->loadProperties($updateAdapter);
    }

    #[\Override]
    public function isConnected(): bool
    {
        return $this->service->isConnected();
    }

    public function isDarkNavigation(): bool
    {
        return $this->getPropertyBoolean(self::P_DARK_NAVIGATION, $this->service->isDarkNavigation());
    }

    #[\Override]
    public function isMessageClose(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_CLOSE, $this->service->isMessageClose());
    }

    #[\Override]
    public function isMessageIcon(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_ICON, $this->service->isMessageIcon());
    }

    #[\Override]
    public function isMessageSubTitle(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_SUB_TITLE, $this->service->isMessageSubTitle());
    }

    #[\Override]
    public function isMessageTitle(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_TITLE, $this->service->isMessageTitle());
    }

    #[\Override]
    public function isPanelCatalog(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_CATALOG, $this->service->isPanelCatalog());
    }

    #[\Override]
    public function isPanelMonth(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_MONTH, $this->service->isPanelMonth());
    }

    #[\Override]
    public function isPanelState(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_STATE, $this->service->isPanelState());
    }

    #[\Override]
    public function isPrintAddress(): bool
    {
        return $this->getPropertyBoolean(self::P_PRINT_ADDRESS, $this->service->isPrintAddress());
    }

    #[\Override]
    public function isQrCode(): bool
    {
        return $this->getPropertyBoolean(self::P_QR_CODE, $this->service->isQrCode());
    }

    #[\Override]
    public function isStatusBar(): bool
    {
        return $this->getPropertyBoolean(self::P_STATUS_BAR, $this->service->isStatusBar());
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
        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        $defaultValues = $this->service->getProperties();
        $existingProperties = $this->getExistingProperties();
        /** @phpstan-var mixed $value */
        foreach ($properties as $key => $value) {
            $this->saveProperty($key, $value, $defaultValues, $existingProperties, $user);
        }
        $this->repository->flush();
        $this->updateAdapter();

        return true;
    }

    /**
     * @return UserProperty[]
     */
    #[\Override]
    protected function loadEntities(): array
    {
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            return $this->repository->findByUser($user);
        }

        return [];
    }

    /**
     * @return array<string, UserProperty>
     */
    private function getExistingProperties(): array
    {
        return $this->mapToKeyValue(
            $this->loadEntities(),
            static fn (UserProperty $property): array => [$property->getName() => $property]
        );
    }

    private function getUser(): ?UserInterface
    {
        return $this->security->getUser();
    }

    /**
     * @param array<string, mixed>        $defaultValues
     * @param array<string, UserProperty> $existingProperties
     */
    private function saveProperty(
        string $name,
        mixed $value,
        array $defaultValues,
        array $existingProperties,
        UserInterface $user
    ): void {
        if ($this->isDefaultValue($defaultValues, $name, $value)) {
            if (isset($existingProperties[$name])) {
                $this->repository->remove($existingProperties[$name], false);
            }
        } else {
            $property = $existingProperties[$name] ?? UserProperty::instance($name, $user);
            $property->setValue($value);
            $this->repository->persist($property, false);
        }
    }
}
