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

use App\Traits\CacheAwareTrait;
use App\Traits\TranslatorAwareTrait;
use App\Utils\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to provide help.
 *
 * @psalm-type HelpActionType = array{
 *     id: string,
 *     icon: string,
 *     description: string,
 *     action?: string}
 * @psalm-type HelpLink = array{
 *     id?: string,
 *     type?: 'dialog'|'entity',
 *     href?: string,
 *     text?: string}
 * @psalm-type HelpForbiddenType = array{
 *     image: string|null,
 *     text:string|null,
 *     action: HelpActionType|null}
 * @psalm-type HelpFieldType = array{
 *      name: string,
 *      description: string|null,
 *      type?: string,
 *      length?: int,
 *      required?: bool,
 *      entity?: string}
 * @psalm-type HelpDialogType = array{
 *      id: string,
 *      description: string|null,
 *      name?: string,
 *      icon?: string,
 *      group?: string,
 *      image: string|null,
 *      images?: string[],
 *      displayEntityColumns: true|null,
 *      displayEntityFields: true|null,
 *      displayEntityActions: true|null,
 *      entity: string|null,
 *      editActions: HelpActionType[]|null,
 *      globalActions: HelpActionType[]|null,
 *      forbidden: HelpForbiddenType|null,
 *      fields?: HelpFieldType[],
 *      details?: string[],
 *      links?: HelpLink[]}
 * @psalm-type HelpEntityType = array{
 *      id: string,
 *      icon: string,
 *      name?: string,
 *      description?: string,
 *      constraints?: string[],
 *      actions: HelpActionType[]|null,
 *      fields: HelpFieldType[]|null,
 *      editActions: HelpActionType|null,
 *      links?: HelpLink[]}
 * @psalm-type HelpMenuType = array{
 *      id: string,
 *      description: string|null,
 *      menus: array|null,
 *      action?: string}
 * @psalm-type HelpMainMenuType = array{
 *      id: string,
 *      image: string|null,
 *      description: string|null,
 *      menus: HelpMenuType[]|null}
 * @psalm-type HelpContentType = array{
 *      actions?: array<string, HelpActionType>,
 *      dialogs?: HelpDialogType[],
 *      entities?: HelpEntityType[],
 *      mainMenu?: HelpMainMenuType|null}
 */
class HelpService implements ServiceSubscriberInterface
{
    use CacheAwareTrait;
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * The image extension.
     */
    final public const IMAGES_EXT = '.png';

    /**
     * The key name to cache content.
     */
    private const CACHE_KEY = 'help';

    /**
     * The cache timeout (15 minutes).
     */
    private const CACHE_TIMEOUT = 60 * 15;

    /** @psalm-var array<string, HelpActionType>|null */
    private ?array $actions = null;
    /** @psalm-var HelpDialogType[]|null */
    private ?array $dialogs = null;
    /** @psalm-var HelpEntityType[]|null */
    private ?array $entities = null;

    /**
     * @param string $file      the absolute path to the JSON help file
     * @param string $imagePath the absolute path to images
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/help/help.json')]
        private readonly string $file,
        #[Autowire('%kernel.project_dir%/public/help/images')]
        private readonly string $imagePath
    ) {
    }

    /**
     * Finds an action for the given identifier.
     *
     * @param string $id the action identifier to search for
     *
     * @psalm-return HelpActionType|null
     */
    public function findAction(string $id): ?array
    {
        return $this->getActions()[$id] ?? null;
    }

    /**
     * Finds a dialog for the given identifier.
     *
     * @param string $id the dialog identifier to search for
     *
     * @return array|null the dialog, if found; null otherwise
     *
     * @pslam-return HelpDialogType|null
     */
    public function findDialog(string $id): ?array
    {
        return $this->getDialogs()[$id] ?? null;
    }

    /**
     * Finds an entity for the given identifier.
     *
     * @param string $id the entity identifier to search for
     *
     * @return array|null the entity, if found; null otherwise
     *
     * @pslam-return HelpEntityType|null
     */
    public function findEntity(string $id): ?array
    {
        return $this->getEntities()[$id] ?? null;
    }

    /**
     * Finds an entity for the given dialog.
     *
     * @param array $dialog the dialog to get the entity to search for
     *
     * @return array|null the entity, if found; null otherwise
     *
     * @psalm-param HelpDialogType $dialog
     *
     * @pslam-return HelpEntityType|null
     */
    public function findEntityByDialog(array $dialog): ?array
    {
        if (isset($dialog['entity'])) {
            return $this->findEntity($dialog['entity']);
        }

        return null;
    }

    /**
     * Gets actions.
     *
     * @psalm-return array<string, HelpActionType>
     */
    public function getActions(): array
    {
        if (null !== $this->actions) {
            return $this->actions;
        }

        return $this->actions = $this->findEntries('actions') ?? [];
    }

    public function getCacheTimeout(): int
    {
        return self::CACHE_TIMEOUT;
    }

    /**
     * Gets the dialogs.
     *
     * @psalm-return HelpDialogType[]
     */
    public function getDialogs(): array
    {
        if (null !== $this->dialogs) {
            return $this->dialogs;
        }

        /** @psalm-var HelpDialogType[]|null $dialogs */
        $dialogs = $this->findEntries('dialogs');
        if (null === $dialogs) {
            return $this->dialogs = [];
        }

        return $this->dialogs = \array_reduce(
            $dialogs,
            /**
             * @psalm-param array<string, HelpDialogType> $carry
             * @psalm-param HelpDialogType $dialog
             */
            static fn (array $carry, array $dialog) => $carry + [$dialog['id'] => $dialog],
            []
        );
    }

    /**
     * Gets the entities.
     *
     * @psalm-return HelpEntityType[]
     */
    public function getEntities(): array
    {
        if (null !== $this->entities) {
            return $this->entities;
        }

        /** @psalm-var HelpEntityType[]|null $entities */
        $entities = $this->findEntries('entities');
        if (null === $entities) {
            return $this->entities = [];
        }

        return $this->entities = \array_reduce(
            $entities,
            /**
             * @psalm-param array<string, HelpEntityType> $carry
             * @psalm-param HelpEntityType $entity
             */
            static fn (array $carry, array $entity) => $carry + [$entity['id'] => $entity],
            []
        );
    }

    /**
     * Gets the absolute path to the JSON help file.
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Gets the full help content.
     */
    public function getHelp(): array
    {
        return (array) ($this->getCacheValue(self::CACHE_KEY, fn (): ?array => $this->loadHelp()) ?? []);
    }

    /**
     * Gets the absolute root path to the images.
     */
    public function getImagePath(): string
    {
        return $this->imagePath;
    }

    /**
     * Gets the main (root) menu.
     *
     * @psalm-return HelpMainMenuType|null
     */
    public function getMainMenu(): ?array
    {
        /** @psalm-var HelpMainMenuType|null $mainMenu */
        $mainMenu = $this->findEntries('mainMenu');

        return $mainMenu;
    }

    /**
     * Gets the main (root) menus.
     *
     * @return array|null the main menus, if found; null otherwise
     *
     * @psalm-return HelpMenuType[]|null
     */
    public function getMainMenus(): ?array
    {
        return $this->findEntries('mainMenu', 'menus');
    }

    /**
     * @psalm-template TKey of array-key
     * @psalm-template TArray
     *
     * @return array<TKey, TArray>|null
     *
     * @phpstan-ignore-next-line
     */
    private function findEntries(string ...$paths): ?array
    {
        $entries = $this->getHelp();
        foreach ($paths as $path) {
            if (!isset($entries[$path])) {
                return null;
            }
            /** @psalm-var array<TKey, TArray> $entries */
            $entries = $entries[$path];
        }

        return $entries;
    }

    /**
     * @psalm-param HelpDialogType $dialog
     */
    private function getDialogGroup(array $dialog): string
    {
        if (isset($dialog['group']) && '' !== $dialog['group']) {
            return $dialog['group'];
        }
        if (isset($dialog['entity']) && '' !== $dialog['entity']) {
            return $dialog['entity'] . '.name';
        }

        return $dialog['id'];
    }

    /**
     * @pslam-return HelpContentType|null
     */
    private function loadHelp(): ?array
    {
        try {
            /** @psalm-var HelpContentType $help */
            $help = FileUtils::decodeJson($this->file);

            if (isset($help['entities']) && [] !== $help['entities']) {
                $entities = &$help['entities'];
                $this->updateEntities($entities);
                $this->sortEntities($entities);
            }

            if (isset($help['dialogs']) && [] !== $help['dialogs']) {
                $dialogs = &$help['dialogs'];
                $this->updateDialogs($dialogs);
                $this->sortDialogs($dialogs);
            }

            return $help;
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @psalm-param HelpDialogType[] $values
     */
    private function sortDialogs(array &$values): void
    {
        \usort($values, function (array $a, array $b): int {
            /**
             * @psalm-var HelpDialogType $a
             * @psalm-var HelpDialogType $b
             */
            $result = \strnatcmp($a['group'] ?? '', $b['group'] ?? '');
            if (0 !== $result) {
                return $result;
            }

            $idA = $a['id'];
            $idB = $b['id'];
            $isListA = \str_ends_with($idA, '.list.title') ? 0 : 1;
            $isListB = \str_ends_with($idB, '.list.title') ? 0 : 1;
            if ($isListA !== $isListB) {
                return $isListA <=> $isListB;
            }

            $nameA = $this->transSplit($idA);
            $nameB = $this->transSplit($idB);

            return \strnatcmp($nameA, $nameB);
        });
    }

    /**
     *  @psalm-param HelpEntityType[] $values
     */
    private function sortEntities(array &$values): void
    {
        \usort($values, function (array $a, array $b): int {
            /**
             * @psalm-var HelpEntityType $a
             * @psalm-var HelpEntityType $b
             */
            $nameA = $a['name'] ?? '';
            $nameB = $b['name'] ?? '';

            return \strnatcmp($nameA, $nameB);
        });
    }

    private function transSplit(string $id): string
    {
        $values = \explode('|', $id);
        if (\count($values) > 1) {
            return $this->trans($values[0], [], $values[1]);
        }

        return $this->trans($values[0]);
    }

    /**
     * @psalm-param HelpDialogType[] $values
     */
    private function updateDialogs(array &$values): void
    {
        /** @psalm-param HelpDialogType $value */
        foreach ($values as &$value) {
            $group = $this->getDialogGroup($value);
            $value['group'] = $this->transSplit($group);
            $value['name'] = $this->transSplit($value['name'] ?? $value['id']);
        }
    }

    /**
     * @psalm-param HelpEntityType[] $values
     */
    private function updateEntities(array &$values): void
    {
        /** @psalm-param HelpEntityType $value */
        foreach ($values as &$value) {
            $value['name'] = $this->trans($value['id'] . '.name');
        }
    }
}
