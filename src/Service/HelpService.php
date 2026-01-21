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

use App\Traits\ArrayTrait;
use App\Traits\ClosureSortTrait;
use App\Traits\TranslatorTrait;
use App\Utils\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to provide help.
 *
 * @phpstan-type HelpActionType = array{
 *     id: string,
 *     icon: string,
 *     description: string,
 *     action?: string}
 * @phpstan-type HelpLink = array{
 *     id?: string,
 *     type?: 'dialog'|'entity',
 *     href?: string,
 *     text?: string}
 * @phpstan-type HelpForbiddenType = array{
 *     image: string|null,
 *     text:string|null,
 *     action: HelpActionType|null}
 * @phpstan-type HelpFieldType = array{
 *      name: string,
 *      description: string|null,
 *      type?: string,
 *      length?: int,
 *      required?: bool,
 *      entity?: string}
 * @phpstan-type HelpDialogType = array{
 *      id: string,
 *      description: string|null,
 *      name?: string,
 *      icon?: string,
 *      group: string,
 *      image: string|null,
 *      images?: string[],
 *      displayEntityColumns: bool|null,
 *      displayEntityFields: bool|null,
 *      displayEntityActions: bool|null,
 *      entity?: string,
 *      editActions: HelpActionType[]|null,
 *      globalActions: HelpActionType[]|null,
 *      forbidden: HelpForbiddenType|null,
 *      fields?: HelpFieldType[],
 *      details?: string[],
 *      links?: HelpLink[]}
 * @phpstan-type HelpEntityType = array{
 *      id: string,
 *      icon: string,
 *      name?: string,
 *      description?: string,
 *      constraints?: string[],
 *      actions: HelpActionType[]|null,
 *      fields: HelpFieldType[]|null,
 *      editActions: HelpActionType|null,
 *      links?: HelpLink[]}
 * @phpstan-type HelpMenuType = array{
 *      id: string,
 *      description: string|null,
 *      menus: array|null,
 *      action?: string}
 * @phpstan-type HelpMainMenuType = array{
 *      image: string|null,
 *      description: string|null,
 *      menus: HelpMenuType[]}
 */
class HelpService
{
    use ArrayTrait;
    use ClosureSortTrait;
    use TranslatorTrait;

    /**
     * The image extension.
     */
    final public const IMAGES_EXT = '.png';

    /**
     * @param string $jsonPath  the absolute path to JSON files
     * @param string $imagePath the absolute path to image files
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/help')]
        private readonly string $jsonPath,
        #[Autowire('%kernel.project_dir%/public/help/images')]
        private readonly string $imagePath,
        #[Target('calculation.help')]
        private readonly CacheInterface $cache,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * Finds an action for the given identifier.
     *
     * @param string $id the action identifier to search for
     *
     * @phpstan-return HelpActionType|null
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
     * @phpstan-return HelpDialogType|null
     */
    public function findDialog(string $id): ?array
    {
        return $this->getDialogs()[$id] ?? null;
    }

    /**
     * Finds an entity for the given identifier.
     *
     * @param string|array|null $id the entity identifier or the dialog array to search for
     *
     * @phpstan-param string|array{entity?: string|null, ...}|null $id
     *
     * @return array|null the entity, if found; null otherwise
     *
     * @phpstan-return HelpEntityType|null
     */
    public function findEntity(string|array|null $id = null): ?array
    {
        if (\is_array($id)) {
            $id = $id['entity'] ?? null;
        }
        if (null === $id || '' === $id) {
            return null;
        }

        return $this->getEntities()[$id] ?? null;
    }

    /**
     * Gets actions.
     *
     * @phpstan-return array<string, HelpActionType>
     */
    public function getActions(): array
    {
        /** @phpstan-var array<string, HelpActionType> */
        return $this->cache->get('help_actions', $this->loadActions(...));
    }

    /**
     * Gets the dialogs.
     *
     * @phpstan-return array<string, HelpDialogType>
     */
    public function getDialogs(): array
    {
        /** @phpstan-var array<string, HelpDialogType> */
        return $this->cache->get('help_dialogs', $this->loadDialogs(...));
    }

    /**
     * @phpstan-return array<string, HelpDialogType[]>
     */
    public function getDialogsByGroup(): array
    {
        $dialogs = $this->getDialogs();
        if ([] === $dialogs) {
            return [];
        }

        return \array_reduce(
            $dialogs,
            static function (array $carry, array $dialog): array {
                $key = $dialog['group'];
                $carry[$key][] = $dialog;

                return $carry;
            },
            []
        );
    }

    /**
     * Gets the entities.
     *
     * @phpstan-return array<string, HelpEntityType>
     */
    public function getEntities(): array
    {
        /** @phpstan-var array<string, HelpEntityType> */
        return $this->cache->get('help_entities', $this->loadEntities(...));
    }

    /**
     * Gets the absolute path of the given image.
     *
     * @param string $image the image name to get the path for
     */
    public function getImageFile(string $image): string
    {
        return FileUtils::buildPath($this->imagePath, $image . self::IMAGES_EXT);
    }

    /**
     * Gets the absolute root path to the images.
     */
    public function getImagePath(): string
    {
        return $this->imagePath;
    }

    /**
     * Gets the root menu.
     *
     * @phpstan-return HelpMainMenuType
     */
    public function getMainMenu(): array
    {
        return $this->cache->get('help_main_menu', $this->loadMainMenu(...));
    }

    /**
     * Gets the root menus.
     *
     * @phpstan-return HelpMenuType[]
     */
    public function getMainMenus(): array
    {
        return $this->getMainMenu()['menus'];
    }

    #[\Override]
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Merge the current item with an action, if applicable.
     *
     * @phpstan-param array{action?: string, ...} $item
     */
    public function mergeAction(array $item): array
    {
        if (!isset($item['action'])) {
            return $item;
        }
        $action = $this->findAction($item['action']);
        if (null === $action) {
            return $item;
        }

        return \array_merge($action, $item);
    }

    /**
     * Sort the given array by the 'name' index.
     *
     * @phpstan-template T of array{name?: string, ...}
     *
     * @phpstan-param array<array-key, T> $array
     */
    public function sortByName(array &$array): void
    {
        \uasort($array, static fn (array $a, array $b): int => ($a['name'] ?? '') <=> ($b['name'] ?? ''));
    }

    private function decodeJson(string $filename): array
    {
        $path = Path::join($this->jsonPath, $filename);

        try {
            return FileUtils::decodeJson($path);
        } catch (\InvalidArgumentException) {
            return [];
        }
    }

    /**
     * @phpstan-return array<string, HelpActionType>
     */
    private function loadActions(): array
    {
        /** @phpstan-var array<string, HelpActionType> */
        return $this->decodeJson('actions.json');
    }

    /**
     * @phpstan-return array<string, HelpDialogType>
     */
    private function loadDialogs(): array
    {
        /** @phpstan-var array<string, HelpDialogType> $dialogs */
        $dialogs = $this->decodeJson('dialogs.json');
        $this->updateDialogs($dialogs);

        return $dialogs;
    }

    /**
     * @phpstan-return array<string, HelpEntityType>
     */
    private function loadEntities(): array
    {
        /** @phpstan-var array<string, HelpEntityType> $entities */
        $entities = $this->decodeJson('entities.json');
        $this->updateEntities($entities);

        return $entities;
    }

    /**
     * @phpstan-return HelpMainMenuType
     */
    private function loadMainMenu(): array
    {
        /** @phpstan-var HelpMainMenuType */
        return $this->decodeJson('main_menu.json');
    }

    /**
     * @phpstan-param array<string, HelpDialogType> $dialogs
     */
    private function updateDialogs(array &$dialogs): void
    {
        foreach ($dialogs as $key => &$dialog) {
            $dialog['id'] = $key;
            $dialog['group'] = $this->trans($dialog['group']);
            $dialog['name'] = $this->trans($dialog['name'] ?? $dialog['id']);
        }

        $this->sortByClosures(
            $dialogs,
            static fn (array $a, array $b): int => \strnatcmp($a['group'], $b['group']),
            static fn (array $a, array $b): int => \str_ends_with($b['id'], '.list.title') <=> \str_ends_with($a['id'], '.list.title'),
            fn (array $a, array $b): int => \strnatcmp($this->trans($a['id']), $this->trans($b['id'])),
        );
    }

    /**
     * @phpstan-param array<string, HelpEntityType> $entities
     */
    private function updateEntities(array &$entities): void
    {
        foreach ($entities as $key => &$entity) {
            $entity['id'] = $key;
            $entity['name'] = $this->trans($entity['id'] . '.name');
        }
        \uasort(
            $entities,
            static fn (array $a, array $b): int => \strnatcmp($a['name'] ?? '', $b['name'] ?? '')
        );
    }
}
