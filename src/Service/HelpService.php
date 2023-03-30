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
use App\Utils\StringUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to provide help.
 *
 *  @psalm-type HelpFieldType = array{
 *      name: string,
 *      description: string,
 *      type: string|null,
 *      length: int|null,
 *      required: bool|null}
 * @psalm-type HelpDialogType = array{
 *      id: string,
 *      description: string|null,
 *      image: string|null,
 *      displayEntityColumns: true|null,
 *      displayEntityFields: true|null,
 *      displayEntityActions: true|null,
 *      entity: null|string,
 *      editActions: null|array,
 *      globalActions: array|null,
 *      forbidden: array|null,
 *      details: string[]|null}
 * @psalm-type HelpEntityType = array{
 *      id: string,
 *      name: string,
 *      description: string|null,
 *      constraints: string[]|null,
 *      actions: array|null,
 *      fields: HelpFieldType[]|null,
 *      required: bool|null,
 *      editActions: array|null}
 * @psalm-type HelpMenuType = array{
 *      id: string,
 *      description: string,
 *      menus: array|null}
 * @psalm-type HelpMainMenuType = array{
 *      id: string,
 *      image: string|null,
 *      description: string|null,
 *      menus: HelpMenuType[]|null}
 * @psalm-type HelpContentType = array{
 *      dialogs: HelpDialogType[]|null,
 *      entities: HelpEntityType[]|null,
 *      mainMenu: HelpMainMenuType|null}
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
     * The cache timeout (15 minutes).
     */
    private const CACHE_TIMEOUT = 60 * 15;

    /**
     * Constructor.
     *
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
     */
    public function findEntityByDialog(array $dialog): ?array
    {
        if (isset($dialog['entity'])) {
            return $this->findEntity($dialog['entity']);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTimeout(): int
    {
        return self::CACHE_TIMEOUT;
    }

    /**
     * Gets the dialogs.
     *
     * @return array|null the dialogs, if found; null otherwise
     *
     * @psalm-return HelpDialogType[]|null
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
     */
    public function getHelp(): array
    {
        /** @psalm-var HelpContentType|null $results */
        $results = $this->getCacheValue(self::CACHE_KEY, fn () => $this->loadHelp());

        return $results ?? [];
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
     */
    public function getMainMenus(): ?array
    {
        /** @psalm-var HelpMenuType[]|null $items */
        $items = $this->findEntries('mainMenu', 'menus');

        return $items;
    }

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
     * @pslam-return HelpContentType|null
     */
    private function loadHelp(): ?array
    {
        if (!\is_string($content = \file_get_contents($this->file))) {
            return null;
        }

        try {
            /** @psalm-var HelpContentType $help */
            $help = StringUtils::decodeJson($content);
            if (!empty($help['dialogs'])) {
                $this->sortDialogs($help['dialogs']);
            }
            if (!empty($help['entities'])) {
                $this->sortEntities($help['entities']);
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
            $textA = $this->trans($a['id'] . '.name');
            $textB = $this->trans($b['id'] . '.name');

            return \strnatcmp($textA, $textB);
        });
    }
}
