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

use App\Traits\TranslatorTrait;
use App\Utils\FileUtils;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Exception\InvalidArgumentException as CacheException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
 *      image: string|null,
 *      description: string|null,
 *      menus: HelpMenuType[]}
 * @psalm-type HelpContentType = array{
 *      actions: array<string, HelpActionType>,
 *      dialogs: array<string, HelpDialogType>,
 *      entities: array<string, HelpEntityType>,
 *      mainMenu: HelpMainMenuType}
 */
class HelpService
{
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
        #[Target('cache.calculation.service.help')]
        private readonly CacheInterface $cache,
        private readonly TranslatorInterface $translator
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
     * @param string|array|null $id the entity identifier or the dialog array to search for
     *
     * @return array|null the entity, if found; null otherwise
     *
     * @psalm-param string|HelpDialogType|null $id
     *
     * @pslam-return HelpEntityType|null
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
     * @psalm-return array<string, HelpActionType>
     */
    public function getActions(): array
    {
        return $this->getHelp()['actions'];
    }

    /**
     * Gets the dialogs.
     *
     * @psalm-return array<string, HelpDialogType>
     */
    public function getDialogs(): array
    {
        return $this->getHelp()['dialogs'];
    }

    /**
     * @psalm-return array<string, HelpDialogType[]>
     *
     * @psalm-api
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
             * @psalm-param array<string, HelpDialogType[]> $carry
             * @psalm-param HelpDialogType $dialog
             *
             * @psalm-return array<string, HelpDialogType[]>
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
     * @psalm-return array<string, HelpEntityType>
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
     * @psalm-return HelpContentType
     */
    public function getHelp(): array
    {
        try {
            /** @psalm-var HelpContentType $help */
            $help = $this->cache->get('help', fn (): array => $this->loadHelp());

            return $help;
        } catch (InvalidArgumentException) {
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
     * @psalm-return HelpMainMenuType
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
     * @psalm-return HelpMenuType[]
     */
    public function getMainMenus(): array
    {
        return $this->getMainMenu()['menus'];
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Merge current item with an action, if applicable.
     *
     * @psalm-param array{action?: string, ...} $item
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
     * Sort the given array by 'name' index.
     *
     * @psalm-template T of array{name?: string, ...}
     *
     * @psalm-param array<array-key, T> $array
     */
    public function sortByName(array &$array): void
    {
        \uasort($array, fn (array $a, array $b): int => ($a['name'] ?? '') <=> ($b['name'] ?? ''));
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
     * @pslam-return HelpContentType
     */
    private function loadHelp(): array
    {
        try {
            /** @psalm-var HelpContentType $help */
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
        } catch (\InvalidArgumentException $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @psalm-param HelpDialogType[] $dialogs
     *
     * @psalm-return array<string, HelpDialogType>
     */
    private function updateDialogs(array $dialogs): array
    {
        /** @psalm-param HelpDialogType $value */
        foreach ($dialogs as &$value) {
            $group = $this->getDialogGroup($value);
            $value['group'] = $this->trans($group);
            $value['name'] = $this->trans($value['name'] ?? $value['id']);
        }

        \usort(
            $dialogs,
            /**
             * @psalm-param HelpDialogType $a
             * @psalm-param HelpDialogType $b
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

        return \array_reduce(
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
     * @psalm-param HelpEntityType[] $entities
     *
     * @psalm-return array<string, HelpEntityType>
     */
    private function updateEntities(array $entities): array
    {
        /** @psalm-param HelpEntityType $value */
        foreach ($entities as &$value) {
            $value['name'] = $this->trans($value['id'] . '.name');
        }

        $this->sortByName($entities);

        return \array_reduce(
            $entities,
            /**
             * @psalm-param array<string, HelpEntityType> $carry
             * @psalm-param HelpEntityType $entity
             */
            static fn (array $carry, array $entity) => $carry + [$entity['id'] => $entity],
            []
        );
    }
}
