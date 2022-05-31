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
use App\Enums\TableView;
use App\Interfaces\ApplicationServiceInterface;
use App\Repository\UserPropertyRepository;
use App\Traits\CacheTrait;
use App\Traits\LoggerTrait;
use App\Traits\TranslatorTrait;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to manage user properties.
 */
class UserService implements ApplicationServiceInterface
{
    use CacheTrait {
        clearCache as traitClearCache;
        saveDeferredCacheValue as traitSaveDeferredCacheValue;
    }
    use LoggerTrait;
    use TranslatorTrait;

    /**
     * The cache saved key.
     */
    private const CACHE_SAVED = 'cache_saved';

    public function __construct(
        private readonly ApplicationService $service,
        private readonly UserPropertyRepository $repository,
        private readonly Security $security,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        CacheItemPoolInterface $userCache
    ) {
        $this->setLogger($logger);
        $this->setTranslator($translator);
        $this->setAdapter($userCache);
    }

    /**
     * Clear this cache.
     */
    public function clearCache(): bool
    {
        if ($this->traitClearCache()) {
            $this->logInfo($this->trans('application_service.clear_success'));

            return true;
        } else {
            $this->logWarning($this->trans('application_service.clear_error'));

            return false;
        }
    }

    /**
     * Gets the display mode for table.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDisplayMode(): TableView
    {
        $default = $this->service->getDisplayMode();
        $value = $this->getPropertyString(self::P_DISPLAY_MODE, $default->value);

        return TableView::tryFrom((string) $value) ?? self::DEFAULT_DISPLAY_MODE;
    }

    /**
     * Gets the action to trigger within the entities.
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
     * Gets the position of the flash bag messages (default: 'bottom-right').
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMessagePosition(): string
    {
        $default = $this->service->getMessagePosition();

        return (string) $this->getPropertyString(self::P_MESSAGE_POSITION, $default);
    }

    /**
     * Gets the timeout, in milliseconds, of the flash bag messages (default: 4000 ms).
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMessageTimeout(): int
    {
        $default = $this->service->getMessageTimeout();

        return $this->getPropertyInteger(self::P_MESSAGE_TIMEOUT, $default);
    }

    /**
     * Returns a value indicating number of displayed calculation in the home page.
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
            self::P_MESSAGE_ICON => $this->isMessageIcon(),
            self::P_MESSAGE_TITLE => $this->isMessageTitle(),
            self::P_MESSAGE_SUB_TITLE => $this->isMessageSubTitle(),
            self::P_MESSAGE_CLOSE => $this->isMessageClose(),
            self::P_MESSAGE_PROGRESS => $this->isMessageProgress(),
            self::P_MESSAGE_POSITION => $this->getMessagePosition(),
            self::P_MESSAGE_TIMEOUT => $this->getMessageTimeout(),

            self::P_DISPLAY_MODE => $this->getDisplayMode(),

            self::P_QR_CODE => $this->isQrCode(),
            self::P_PRINT_ADDRESS => $this->isPrintAddress(),

            self::P_PANEL_CALCULATION => $this->getPanelCalculation(),
            self::P_PANEL_STATE => $this->isPanelState(),
            self::P_PANEL_MONTH => $this->isPanelMonth(),
            self::P_PANEL_CATALOG => $this->isPanelCatalog(),
        ];
    }

    /**
     * Gets an integer property.
     *
     * @param string $name    the property name to search for
     * @param int    $default the default value if the property is not found
     *
     * @throws \Psr\Cache\InvalidArgumentException
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
     *
     * @throws \Psr\Cache\InvalidArgumentException
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
     * Returns a value indicating if the default action is to edit the entity.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isActionEdit(): bool
    {
        return EntityAction::EDIT === $this->getEditAction();
    }

    /**
     * Returns a value indicating if the default action is to do nothing.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isActionNone(): bool
    {
        return EntityAction::NONE === $this->getEditAction();
    }

    /**
     * Returns a value indicating if the default action is to show the entity.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isActionShow(): bool
    {
        return EntityAction::SHOW === $this->getEditAction();
    }

    /**
     * Returns if the flash bag message icon is displayed (default: true).
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageClose(): bool
    {
        $default = $this->service->isMessageClose();

        return $this->isPropertyBoolean(self::P_MESSAGE_CLOSE, $default);
    }

    /**
     * Returns if the flash bag message icon is displayed (default: true).
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageIcon(): bool
    {
        $default = $this->service->isMessageIcon();

        return $this->isPropertyBoolean(self::P_MESSAGE_ICON, $default);
    }

    /**
     * Returns if the flash bag message progress bar is displayed (default: true).
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageProgress(): bool
    {
        $default = $this->service->isMessageProgress();

        return $this->isPropertyBoolean(self::P_MESSAGE_PROGRESS, $default);
    }

    /**
     * Returns if the flash bag message subtitle is displayed (default: true).
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageSubTitle(): bool
    {
        $default = $this->service->isMessageSubTitle();

        return $this->isPropertyBoolean(self::P_MESSAGE_SUB_TITLE, $default);
    }

    /**
     * Returns if the flash bag message title is displayed (default: true).
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
     * Gets a boolean property.
     *
     * @param string $name    the property name to search for
     * @param bool   $default the default value if the property is not found
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPropertyBoolean(string $name, bool $default = false): bool
    {
        return (bool) $this->getItemValue($name, $default);
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

    public function saveDeferredCacheValue(string $key, mixed $value, int|\DateInterval|null $time = null): bool
    {
        if (!$this->traitSaveDeferredCacheValue($key, $value, $time)) {
            $this->logWarning($this->trans('application_service.deferred_error', ['%key%' => $key]));

            return false;
        }

        return true;
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
     * Sets a single property value.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function setProperty(string $name, mixed $value): self
    {
        return $this->setProperties([$name => $value]);
    }

    /**
     * Update the cache if needed.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function updateCache(): void
    {
        if (!$this->getCacheValue(self::CACHE_SAVED, false)) {
            $this->updateAdapter();
        }
    }

    /**
     * Gets an item value.
     *
     * @param string $name    the item name
     * @param mixed  $default the default value if the item is not found
     *
     * @return mixed the value, if hit; the default value otherwise
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function getItemValue(string $name, mixed $default): mixed
    {
        $item = $this->getCacheItem($name);
        if (null !== $item && $item->isHit()) {
            return $item->get();
        }

        return $default;
    }

    private function getUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
    }

    private function isDefaultValue(array $defaultProperties, string $name, mixed $value): bool
    {
        return \array_key_exists($name, $defaultProperties) && $defaultProperties[$name] === $value;
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

    /**
     * Update the content of the cache from the repository.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function updateAdapter(): void
    {
        $this->clearCache();
        if (null !== $user = $this->getUser()) {
            $properties = $this->repository->findByUser($user);
            foreach ($properties as $property) {
                $this->saveDeferredCacheValue($property->getName(), $property->getString());
            }
            $this->saveDeferredCacheValue(self::CACHE_SAVED, true);
            if ($this->commitDeferredValues()) {
                $this->logInfo($this->trans('application_service.commit_success'));
            } else {
                $this->logWarning($this->trans('application_service.commit_error'));
            }
        }
    }
}
