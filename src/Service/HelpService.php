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
use App\Traits\TranslatorTrait;
use App\Utils\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
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
 * @phpstan-type HelpContentType = array{
 *      actions: array<string, HelpActionType>,
 *      dialogs: array<string, HelpDialogType>,
 *      entities: array<string, HelpEntityType>,
 *      mainMenu: HelpMainMenuType}
 */
class HelpService
{
    use ArrayTrait;
    use TranslatorTrait;

    /**
     * The image extension.
     */
    final public const IMAGES_EXT = '.png';

    /**
     * @param string $file      the absolute path to the JSON help file
     * @param string $imagePath the absolute path to images
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/help/help.json')]
        private readonly string $file,
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
     * @return array|null the entity, if found; null otherwise
     *
     * @phpstan-param string|HelpDialogType|null $id
     *
     * @phpstan-return HelpEntityType|null
     */
    public function findEntity(string|array|null $id = null): ?array
    {
        if (\is_array($id)) {
            $id = $id['entity'] ?? '';
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
        return $this->getHelp()['actions'];
    }

    /**
     * Gets the dialogs.
     *
     * @phpstan-return array<string, HelpDialogType>
     */
    public function getDialogs(): array
    {
        return $this->getHelp()['dialogs'];
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
            /**
             * @phpstan-param array<string, HelpDialogType[]> $carry
             * @phpstan-param HelpDialogType $dialog
             *
             * @phpstan-return array<string, HelpDialogType[]>
             */
            function (array $carry, array $dialog): array {
                $key = $dialog['group'] ?? '';
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
        return $this->getHelp()['entities'];
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
     *
     * @phpstan-return HelpContentType
     */
    public function getHelp(): array
    {
        try {
            /** @phpstan-var HelpContentType */
            return $this->cache->get('help', fn (): array => $this->loadHelp());
        } catch (\InvalidArgumentException) {
            return [
                'actions' => [],
                'dialogs' => [],
                'entities' => [],
                'mainMenu' => [
                    'image' => null,
                    'description' => null,
                    'menus' => [],
                ],
            ];
        }
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
     * @phpstan-return HelpMainMenuType
     */
    public function getMainMenu(): array
    {
        return $this->getHelp()['mainMenu'];
    }

    /**
     * Gets the main (root) menus.
     *
     * @return array the main menus, if found; null otherwise
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
        \uasort($array, fn (array $a, array $b): int => ($a['name'] ?? '') <=> ($b['name'] ?? ''));
    }

    /**
     * @phpstan-param HelpDialogType $dialog
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
     * @phpstan-return HelpContentType
     */
    private function loadHelp(): array
    {
        /** @phpstan-var HelpContentType $help */
        $help = FileUtils::decodeJson($this->file);
        $entities = $help['entities'];
        if ([] !== $entities) {
            $help['entities'] = $this->updateEntities($entities);
        }
        $dialogs = $help['dialogs'];
        if ([] !== $dialogs) {
            $help['dialogs'] = $this->updateDialogs($dialogs);
        }

        return $help;
    }

    /**
     * @phpstan-param HelpDialogType[] $dialogs
     *
     * @phpstan-return array<string, HelpDialogType>
     */
    private function updateDialogs(array $dialogs): array
    {
        foreach ($dialogs as &$dialog) {
            $group = $this->getDialogGroup($dialog);
            $dialog['group'] = $this->trans($group);
            $dialog['name'] = $this->trans($dialog['name'] ?? $dialog['id']);
        }

        \usort(
            $dialogs,
            /**
             * @phpstan-param HelpDialogType $a
             * @phpstan-param HelpDialogType $b
             */
            function (array $a, array $b): int {
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

                $nameA = $this->trans($idA);
                $nameB = $this->trans($idB);

                return \strnatcmp($nameA, $nameB);
            }
        );

        /** @phpstan-var array<string, HelpDialogType> */
        return $this->mapToKeyValue(
            $dialogs,
            /** @phpstan-param HelpDialogType $dialog */
            fn (array $dialog): array => [$dialog['id'] => $dialog]
        );
    }

    /**
     * @phpstan-param HelpEntityType[] $entities
     *
     * @phpstan-return array<string, HelpEntityType>
     */
    private function updateEntities(array $entities): array
    {
        foreach ($entities as &$entity) {
            $entity['name'] = $this->trans($entity['id'] . '.name');
        }
        $this->sortByName($entities);

        /** @phpstan-var array<string, HelpEntityType> */
        return $this->mapToKeyValue(
            $entities,
            /** @phpstan-param HelpEntityType $entity  */
            fn (array $entity): array => [$entity['id'] => $entity]
        );
    }
}
