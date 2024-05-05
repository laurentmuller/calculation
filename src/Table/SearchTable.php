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

namespace App\Table;

use App\Interfaces\TableInterface;
use App\Service\SearchService;
use App\Traits\AuthorizationCheckerAwareTrait;
use App\Traits\TranslatorAwareTrait;
use App\Utils\FileUtils;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * The search table.
 *
 * @psalm-import-type SearchType from SearchService
 */
class SearchTable extends AbstractTable implements ServiceSubscriberInterface
{
    use AuthorizationCheckerAwareTrait;
    use ServiceMethodsSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * The entity parameter name.
     */
    final public const PARAM_ENTITY = 'entity';

    /**
     * The type parameter name.
     */
    final public const PARAM_TYPE = 'type';

    /**
     * The default sort columns order.
     */
    private const SORT_COLUMNS = [
        SearchService::COLUMN_CONTENT,
        SearchService::COLUMN_ENTITY_NAME,
        SearchService::COLUMN_FIELD_NAME,
    ];

    public function __construct(private readonly SearchService $service)
    {
    }

    protected function getAllowedPageList(int $totalNotFiltered): array
    {
        return TableInterface::PAGE_LIST;
    }

    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'search.json');
    }

    protected function handleQuery(DataQuery $query): DataResults
    {
        $items = [];
        $search = $query->search;
        $entity = $query->entity;
        $results = parent::handleQuery($query);
        if (\strlen($search) > 1) {
            $items = $this->service->search($search, $entity, SearchService::NO_LIMIT);
            $results->totalNotFiltered = $results->filtered = \count($items);
            if (0 !== $results->totalNotFiltered) {
                $this->processItems($items);
                $this->sortItems($items, $query->sort, $query->order);
                $items = \array_slice($items, $query->offset, $query->limit);
                foreach ($items as &$item) {
                    $this->updateItem($item);
                }
            }
        }
        $results->rows = $items;
        if ($query->callback) {
            return $results;
        }

        $entities = $this->service->getEntities();
        foreach ($entities as $key => &$value) {
            $value = [
                'name' => $value,
                'icon' => $this->getIcon($key),
            ];
        }
        $results->customData = [
            'entity' => $entity,
            'entities' => $entities,
        ];
        $results->addParameter(self::PARAM_ENTITY, $entity);

        return $results;
    }

    /**
     * Gets icon for the given entity type.
     */
    private function getIcon(string $type): string
    {
        return match ($type) {
            'calculation' => 'fa-solid fa-calculator',
            'calculationstate' => 'fa-regular fa-flag',
            'category' => 'fa-regular fa-folder',
            'customer' => 'fa-regular fa-address-card',
            'task' => 'fa-solid fa-tasks',
            'group' => 'fa-solid fa-code-branch',
            'product' => 'fa-regular fa-file-alt',
            default => 'fa-regular fa-file',
        };
    }

    /**
     * Update items.
     *
     * @param SearchType[] $items
     */
    private function processItems(array &$items): void
    {
        foreach ($items as &$item) {
            $type = $item[SearchService::COLUMN_TYPE];
            $field = $item[SearchService::COLUMN_FIELD];
            $lowerType = \strtolower($type);

            $item[SearchService::COLUMN_ACTION] = $item['id'];
            $item[SearchService::COLUMN_ENTITY_NAME] = $this->trans("$lowerType.name");
            $item[SearchService::COLUMN_FIELD_NAME] = $this->trans("$lowerType.fields.$field");
            $item[SearchService::COLUMN_CONTENT] = $this->service->formatContent("$type.$field", $item[SearchService::COLUMN_CONTENT]);

            $item[SearchService::COLUMN_GRANTED_SHOW] = $this->isGrantedShow($type);
            $item[SearchService::COLUMN_GRANTED_EDIT] = $this->isGrantedEdit($type);
            $item[SearchService::COLUMN_GRANTED_DELETE] = $this->isGrantedDelete($type);
        }
    }

    /**
     * Sorts items.
     *
     * @param SearchType[] $items
     */
    private function sortItems(array &$items, string $sort, string $order): void
    {
        $columns = [$sort => self::SORT_ASC === $order ? 1 : -1];
        foreach (self::SORT_COLUMNS as $field) {
            if (!\array_key_exists($field, $columns)) {
                $columns[$field] = 1;
            }
        }
        \usort($items, function (array $a, array $b) use ($columns): int {
            foreach ($columns as $key => $order) {
                $result = \strnatcasecmp((string) $a[$key], (string) $b[$key]);
                if (0 !== $result) {
                    return $result * $order;
                }
            }

            return 0;
        });
    }

    /**
     * Update the item.
     *
     * @psalm-param SearchType $item
     *
     * @psalm-param-out array{
     *       id: int,
     *       type: string,
     *       content: string,
     *       entityName: string,
     *       fieldName: string
     *   } $item
     */
    private function updateItem(array &$item): void
    {
        $name = $item[SearchService::COLUMN_ENTITY_NAME];
        $type = \strtolower($item[SearchService::COLUMN_TYPE]);
        $icon = $this->getIcon($type);
        $item[SearchService::COLUMN_ENTITY_NAME] = \sprintf('<i class="%s me-1"></i>%s', $icon, $name);
        $item[SearchService::COLUMN_TYPE] = $type;
        unset($item[SearchService::COLUMN_FIELD]);
    }
}
