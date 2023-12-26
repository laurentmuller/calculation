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
use App\Traits\PropertyServiceTrait;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to manage user properties.
 */
class UserService implements PropertyServiceInterface, ServiceSubscriberInterface
{
    use PropertyServiceTrait;

    public function __construct(
        private readonly UserPropertyRepository $repository,
        private readonly ApplicationService $service,
        private readonly Security $security,
        #[Target('cache.user_service')]
        CacheItemPoolInterface $cacheItemPool
    ) {
        $this->setCacheItemPool($cacheItemPool);
    }

    /**
     * Gets the application service.
     */
    public function getApplication(): ApplicationService
    {
        return $this->service;
    }

    public function getCustomer(): CustomerInformation
    {
        $customer = $this->service->getCustomer();
        $customer->setPrintAddress($this->isPrintAddress());

        return $customer;
    }

    public function getDisplayMode(): TableView
    {
        return $this->getPropertyEnum(self::P_DISPLAY_MODE, $this->service->getDisplayMode());
    }

    public function getEditAction(): EntityAction
    {
        return $this->getPropertyEnum(self::P_EDIT_ACTION, $this->service->getEditAction());
    }

    /**
     * Gets the message attributes.
     *
     * @psalm-api
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

    public function getMessagePosition(): MessagePosition
    {
        return $this->getPropertyEnum(self::P_MESSAGE_POSITION, $this->service->getMessagePosition());
    }

    public function getMessageProgress(): int
    {
        return $this->getPropertyInteger(self::P_MESSAGE_PROGRESS, $this->service->getMessageProgress());
    }

    public function getMessageTimeout(): int
    {
        return $this->getPropertyInteger(self::P_MESSAGE_TIMEOUT, $this->service->getMessageTimeout());
    }

    public function getPanelCalculation(): int
    {
        return $this->getPropertyInteger(self::P_PANEL_CALCULATION, $this->service->getPanelCalculation());
    }

    /**
     * Gets all properties.
     *
     * @return array<string, mixed>
     */
    public function getProperties(): array
    {
        return $this->loadProperties();
    }

    public function isDarkNavigation(): bool
    {
        return $this->getPropertyBoolean(self::P_DARK_NAVIGATION, $this->service->isDarkNavigation());
    }

    public function isMessageClose(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_CLOSE, $this->service->isMessageClose());
    }

    public function isMessageIcon(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_ICON, $this->service->isMessageIcon());
    }

    public function isMessageSubTitle(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_SUB_TITLE, $this->service->isMessageSubTitle());
    }

    public function isMessageTitle(): bool
    {
        return $this->getPropertyBoolean(self::P_MESSAGE_TITLE, $this->service->isMessageTitle());
    }

    public function isPanelCatalog(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_CATALOG, $this->service->isPanelCatalog());
    }

    public function isPanelMonth(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_MONTH, $this->service->isPanelMonth());
    }

    public function isPanelState(): bool
    {
        return $this->getPropertyBoolean(self::P_PANEL_STATE, $this->service->isPanelState());
    }

    public function isPrintAddress(): bool
    {
        return $this->getPropertyBoolean(self::P_PRINT_ADDRESS, $this->service->isPrintAddress());
    }

    public function isQrCode(): bool
    {
        return $this->getPropertyBoolean(self::P_QR_CODE, $this->service->isQrCode());
    }

    public function isStatusBar(): bool
    {
        return $this->getPropertyBoolean(self::P_STATUS_BAR, $this->service->isStatusBar());
    }

    /**
     * @param array<string, mixed> $properties
     */
    public function setProperties(array $properties): static
    {
        if ([] === $properties) {
            return $this;
        }
        if (!$this->isPropertiesChanged($properties, new UserProperty())) {
            return $this;
        }
        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            return $this;
        }

        $defaultValues = $this->service->getProperties();
        $existingProperties = $this->getExistingProperties($user);
        /** @psalm-var mixed $value */
        foreach ($properties as $key => $value) {
            $this->saveProperty($key, $value, $defaultValues, $existingProperties, $user);
        }
        $this->repository->flush();
        $this->updateAdapter();

        return $this;
    }

    protected function updateAdapter(): void
    {
        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            return;
        }
        $properties = $this->repository->findByUser($user);
        $this->saveProperties($properties);
    }

    /**
     * @psalm-return array<string, UserProperty>
     */
    private function getExistingProperties(UserInterface $user): array
    {
        $properties = $this->repository->findByUser($user);

        return \array_reduce(
            $properties,
            /** @psalm-param array<string, UserProperty> $carry */
            fn (array $carry, UserProperty $property) => $carry + [$property->getName() => $property],
            []
        );
    }

    private function getUser(): ?UserInterface
    {
        return $this->security->getUser();
    }

    /**
     * @psalm-param array<string, mixed> $defaultValues
     * @psalm-param array<string, UserProperty> $existingProperties
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
