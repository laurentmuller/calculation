<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Service;

use App\Traits\CacheTrait;
use App\Traits\TranslatorTrait;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to provide help.
 *
 * @author Laurent Muller
 */
class HelpService
{
    use CacheTrait;
    use TranslatorTrait;

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
     *
     * @var string
     */
    private $file;

    /**
     * The absolute root path to the images.
     *
     * @var string
     */
    private $imagePath;

    /**
     * Constructor.
     */
    public function __construct(AdapterInterface $adapter, TranslatorInterface $translator, KernelInterface $kernel)
    {
        if (!$kernel->isDebug()) {
            $this->adapter = $adapter;
        }
        $this->translator = $translator;
        $this->file = $kernel->getProjectDir() . self::FILE_PATH;
        $this->imagePath = $kernel->getProjectDir() . self::IMAGE_PATH;
    }

    /**
     * Finds a dialog for the given identifier.
     *
     * @param string $id the dialog identifier to search for
     *
     * @return array|null the dialog, if found; null otherwise
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
     */
    public function findEntityByDialog(array $dialog): ?array
    {
        if ($id = $dialog['entity'] ?? false) {
            return $this->findEntity($id);
        }

        return null;
    }

    /**
     * Gets the dialogs.
     *
     * @return array|null the dialogs, if found; null otherwise
     */
    public function getDialogs(): ?array
    {
        return $this->findEntries('dialogs');
    }

    /**
     * Gets the entities.
     *
     * @return array|null the entities, if found; null otherwise
     */
    public function getEntities(): ?array
    {
        return $this->findEntries('entities');
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
        // read from cache
        if ($help = $this->getCacheValue(self::CACHE_KEY)) {
            return $help;
        }

        // load
        $content = \file_get_contents($this->file);
        if ($help = \json_decode($content, true)) {
            // sort
            if (isset($help['entities'])) {
                $this->sortEntities($help['entities']);
            }
            if (isset($help['dialogs'])) {
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
     */
    public function getMainMenu(): ?array
    {
        return $this->findEntries('mainMenu');
    }

    /**
     * Gets the main (root) menus.
     *
     * @return array|null the main menus, if found; null otherwise
     */
    public function getMainMenus(): ?array
    {
        return $this->findEntries('mainMenu', 'menus');
    }

    private function findById(string $path, string $id): ?array
    {
        if ($entries = $this->findEntries($path)) {
            foreach ($entries as $entry) {
                if (isset($entry['id']) && $entry['id'] === $id) {
                    return $entry;
                }
            }
        }

        return null;
    }

    private function findEntries(string ...$paths): ?array
    {
        $entries = $this->getHelp();
        foreach ($paths as $path) {
            if (!isset($entries[$path])) {
                return null;
            }
            $entries = $entries[$path];
        }

        return $entries;
    }

    private function sortDialogs(array &$values): void
    {
        \usort($values, function (array $a, array $b) {
            $entityA = isset($a['entity']) ? $this->trans($a['entity'] . '.name') : 'zzzz';
            $entityB = isset($b['entity']) ? $this->trans($b['entity'] . '.name') : 'zzzz';
            if (0 !== $result = \strnatcmp($entityA, $entityB)) {
                return $result;
            }

            $textA = $this->trans($a['id']);
            $textB = $this->trans($b['id']);

            return \strnatcmp($textA, $textB);
        });
    }

    private function sortEntities(array &$values): void
    {
        \usort($values, function (array $a, array $b) {
            $textA = $this->trans($a['id'] . '.name');
            $textB = $this->trans($b['id'] . '.name');

            return \strnatcmp($textA, $textB);
        });
    }
}
