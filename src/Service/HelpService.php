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
 * @psalm-type  HelpDialogType = array{
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
 *      details: string[]|null}
 * @psalm-type HelpEntityType = array{
 *      id: string,
 *      name: string,
 *      description: string|null,
 *      constraints: string[]|null,
 *      actions: array|null,
 *      fields: array|null,
 *      required: bool|null}
 * @psalm-type HelpMenuType = array{
 *      id: string,
 *      description: string,
 *      menus: array[]|null}
 * @psalm-type HelpMainMenuType = array{
 *      id: string,
 *      image: string|null,
 *      description: string|null,
 *      menus: HelpMenuType[]|null}
 * @psalm-type HelpContentType = array{
 *      dialogs: HelpDialogType[]|null,
 *      entities: HelpEntityType[]|null,
 *      mainMenu: HelpMainMenuType|null}
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
     * @pslam-return HelpDialogType|null
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
     * @pslam-return HelpEntityType|null
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
     * @psalm-param HelpDialogType $dialog
     *
     * @pslam-return HelpEntityType|null
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findEntityByDialog(array $dialog): ?array
    {
        if (isset($dialog['entity'])) {
            return $this->findEntity($dialog['entity']);
        }

        return null;
    }

    /**
     * Gets the dialogs.
     *
     * @return array|null the dialogs, if found; null otherwise
     *
     * @psalm-return HelpDialogType[]|null
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDialogs(): ?array
    {
        /** @psalm-var HelpDialogType[]|null $items */
        $items = $this->findEntries('dialogs');

        return $items;
    }

    /**
     * Gets the entities.
     *
     * @return array|null the entities, if found; null otherwise
     *
     * @psalm-return HelpEntityType[]|null
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getEntities(): ?array
    {
        /** @psalm-var HelpEntityType[]|null $items */
        $items = $this->findEntries('entities');

        return $items;
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
        /** @psalm-var HelpContentType|null $help */
        $help = $this->getCacheValue(self::CACHE_KEY);
        if (\is_array($help)) {
            return $help;
        }

        // load
        $content = (string) \file_get_contents($this->file);

        /** @psalm-var HelpContentType|null $help */
        $help = \json_decode($content, true);
        if (\is_array($help)) {
            if (!empty($help['dialogs'])) {
                $this->sortDialogs($help['dialogs']);
            }
            if (!empty($help['entities'])) {
                $this->sortEntities($help['entities']);
            }
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
     * @return HelpMainMenuType|null the main menu, if found; null otherwise
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMainMenu(): ?array
    {
        /** @psalm-var HelpMainMenuType|null $items */
        $items = $this->findEntries('mainMenu');

        return $items;
    }

    /**
     * Gets the main (root) menus.
     *
     * @return array|null the main menus, if found; null otherwise
     *
     * @psalm-return HelpMenuType[]|null
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMainMenus(): ?array
    {
        /** @psalm-var HelpMenuType[]|null $items */
        $items = $this->findEntries('mainMenu', 'menus');

        return $items;
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
     * @psalm-param HelpDialogType[] $values
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
     *  @psalm-param HelpEntityType[] $values
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
