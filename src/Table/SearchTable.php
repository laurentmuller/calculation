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
use App\Util\FileUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * The search table.
 *
 * @psalm-import-type SearchType from SearchService
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SearchTable extends AbstractTable implements ServiceSubscriberInterface
{
    use AuthorizationCheckerAwareTrait;
    use ServiceSubscriberTrait;
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

    /**
     * Constructor.
     */
    public function __construct(private readonly SearchService $service)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getDataQuery(Request $request): DataQuery
    {
        $query = parent::getDataQuery($request);
        $entity = (string) $this->getRequestString($request, self::PARAM_ENTITY, '');
        $query->addCustomData(self::PARAM_ENTITY, $entity);

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAllowedPageList(int $totalNotFiltered): array
    {
        return TableInterface::PAGE_LIST;
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'search.json');
    }

    /**
     * {@inheritDoc}
     *
     * @throws \ReflectionException
     */
    protected function handleQuery(DataQuery $query): DataResults
    {
        /** SearchType[] $items */
        $items = [];
        $search = $query->search;
        $entity = (string) $query->customData[self::PARAM_ENTITY];
        $results = parent::handleQuery($query);

        // search?
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

        // ajax?
        if (!$query->callback) {
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
        }

        return $results;
    }

    /**
     * Gets icon for the given entity type.
     */
    private function getIcon(string $type): string
    {
        return match ($type) {
            'calculation' => 'fa-fw fa-solid fa-calculator',
            'calculationstate' => 'fa-fw fa-regular fa-flag',
            'category' => 'fa-fw fa-regular fa-folder',
            'customer' => 'fa-fw fa-regular fa-address-card',
            'task' => 'fa-fw fa-solid fa-tasks',
            'group' => 'fa-fw fa-solid fa-code-branch',
            'product' => 'fa-fw fa-regular fa-file-alt',
            default => 'fa-fw fa-regular fa-file',
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

            // set authorizations
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
     * @psalm-param-out  array<"id"|"type"|"field"|"content"|"entityName"|"fieldName", int|string> $item
     */
    private function updateItem(array &$item): void
    {
        $name = $item[SearchService::COLUMN_ENTITY_NAME];
        $type = \strtolower($item[SearchService::COLUMN_TYPE]);
        $icon = $this->getIcon($type);

        $item[SearchService::COLUMN_ENTITY_NAME] = \sprintf('<i class="%s" aria-hidden="true"></i>&nbsp;%s', $icon, $name);
        $item[SearchService::COLUMN_TYPE] = $type;
        unset($item[SearchService::COLUMN_FIELD]);
    }
}
