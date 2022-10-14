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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to provide help.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class HelpService implements ServiceSubscriberInterface
{
    use CacheAwareTrait;
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * The key name to cache content.
     */
    private const CACHE_KEY = 'help';

    /**
     * The relative path to the JSON help file.
     */
    private const FILE_PATH = '/public/help/help.json';

    /**
     * The relative path to the images.
     */
    private const IMAGE_PATH = '/public/help/images/';

    /**
     * The absolute path to the JSON help file.
     */
    private readonly string $file;

    /**
     * The absolute root path to the images.
     */
    private readonly string $imagePath;

    /**
     * Constructor.
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $projectDir
    ) {
        $this->file = $projectDir . self::FILE_PATH;
        $this->imagePath = $projectDir . self::IMAGE_PATH;
    }

    /**
     * Finds a dialog for the given identifier.
     *
     * @param string $id the dialog identifier to search for
     *
     * @return array|null the dialog, if found; null otherwise
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findDialog(string $id): ?array
    {
        return $this->findById('dialogs', $id);
    }

    /**
     * Finds an entity for the given identifier.
     *
     * @param string $id the entity identifier to search for
     *
     * @return array|null the entity, if found; null otherwise
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findEntity(string $id): ?array
    {
        return $this->findById('entities', $id);
    }

    /**
     * Finds an entity for the given dialog.
     *
     * @param array $dialog the dialog to get the entity to search for
     *
     * @return array|null the entity, if found; null otherwise
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findEntityByDialog(array $dialog): ?array
    {
        /** @var string|null $id */
        $id = $dialog['entity'] ?? null;
        if (null !== $id) {
            return $this->findEntity($id);
        }

        return null;
    }

    /**
     * Gets the dialogs.
     *
     * @return array|null the dialogs, if found; null otherwise
     *
     * @psalm-return null|array<array{
     *      id: string,
     *      description: string|null,
     *      image: string|null,
     *      displayEntityColumns: null|bool,
     *      displayEntityFields: null|bool,
     *      displayEntityActions: null|bool,
     *      entity: null|string,
     *      editActions: null|array,
     *      globalActions: null|array,
     *      forbidden: null|array,
     *      details: string[]|null}>
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDialogs(): ?array
    {
        /**
         * @psalm-var null|array<array{
         *      id: string,
         *      description: string|null,
         *      image: string|null,
         *      displayEntityColumns: null|bool,
         *      displayEntityFields: null|bool,
         *      displayEntityActions: null|bool,
         *      entity: null|string,
         *      editActions: null|array,
         *      globalActions: null|array,
         *      forbidden: null|array,
         *      details: string[]|null}> $dialogs
         */
        $dialogs = $this->findEntries('dialogs');

        return $dialogs;
    }

    /**
     * Gets the entities.
     *
     * @return array|null the entities, if found; null otherwise
     *
     * @psalm-return null|array<array{
     *      id: string,
     *      name: string,
     *      description: string|null,
     *      constraints: string[]|null,
     *      actions: array|null,
     *      fields: array|null,
     *      required: bool|null}>
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getEntities(): ?array
    {
        /**
         * @psalm-var null|array<array{
         *      id: string,
         *      name: string,
         *      description: string|null,
         *      constraints: string[]|null,
         *      actions: array|null,
         *      fields: array|null,
         *      required: bool|null}> $entities
         */
        $entities = $this->findEntries('entities');

        return $entities;
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
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getHelp(): array
    {
        // read from cache
        /** @psalm-var array|null $help */
        $help = $this->getCacheValue(self::CACHE_KEY);
        if (\is_array($help)) {
            return $help;
        }

        // load
        $content = (string) \file_get_contents($this->file);
        /**
         * @psalm-var null|array{
         *      entities: null|bool|array<array{id: string}>,
         *      dialogs:  null|bool|array<array{id: string, entity: null|string}>
         * } $help
         */
        $help = \json_decode($content, true);
        if (\is_array($help)) {
            if (isset($help['entities']) && \is_array($help['entities'])) {
                $this->sortEntities($help['entities']);
            }
            if (isset($help['dialogs']) && \is_array($help['dialogs'])) {
                $this->sortDialogs($help['dialogs']);
            }

            // save to cache
            $this->setCacheValue(self::CACHE_KEY, $help);

            return $help;
        }

        return [];
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
     * @return array|null the main menu, if found; null otherwise
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMainMenu(): ?array
    {
        return $this->findEntries('mainMenu');
    }

    /**
     * Gets the main (root) menus.
     *
     * @return array|null the main menus, if found; null otherwise
     *
     * @psalm-return null|array<array{
     *      id: string,
     *      description:
     *      string,
     *      menus: array|null}>
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMainMenus(): ?array
    {
        /**
         * @psalm-var null|array<array{
         *      id: string,
         *      description: string,
         *      menus: array|null}> $menus
         */
        $menus = $this->findEntries('mainMenu', 'menus');

        return $menus;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function findById(string $path, string $id): ?array
    {
        if ($entries = $this->findEntries($path)) {
            /** @psalm-var array $entry */
            foreach ($entries as $entry) {
                if (isset($entry['id']) && $entry['id'] === $id) {
                    return $entry;
                }
            }
        }

        return null;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function findEntries(string ...$paths): ?array
    {
        $entries = $this->getHelp();
        foreach ($paths as $path) {
            if (!isset($entries[$path])) {
                return null;
            }
            /** @psalm-var array $entries */
            $entries = $entries[$path];
        }

        return $entries;
    }

    /**
     * @psalm-param array<array{entity: string|null, id: string}> $values
     */
    private function sortDialogs(array &$values): void
    {
        \usort($values, function (array $a, array $b) {
            $entityA = isset($a['entity']) ? $this->trans((string) $a['entity'] . '.name') : 'zzzz';
            $entityB = isset($b['entity']) ? $this->trans((string) $b['entity'] . '.name') : 'zzzz';
            if (0 !== $result = \strnatcmp($entityA, $entityB)) {
                return $result;
            }

            $textA = $this->trans((string) $a['id']);
            $textB = $this->trans((string) $b['id']);

            return \strnatcmp($textA, $textB);
        });
    }

    /**
     *  @psalm-param array<array{id: string}> $values
     */
    private function sortEntities(array &$values): void
    {
        \usort($values, function (array $a, array $b) {
            $textA = $this->trans((string) $a['id'] . '.name');
            $textB = $this->trans((string) $b['id'] . '.name');

            return \strnatcmp($textA, $textB);
        });
    }
}
