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

use App\Interfaces\SortModeInterface;
use App\Interfaces\TableInterface;
use App\Service\SearchService;
use App\Traits\CheckerAwareTrait;
use App\Traits\TranslatorAwareTrait;
use App\Util\FileUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * The search table.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SearchTable extends AbstractTable implements ServiceSubscriberInterface
{
    use CheckerAwareTrait;
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
        $entity = (string) $this->getRequestValue($request, self::PARAM_ENTITY, '', false);
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
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return [
            SearchService::COLUMN_CONTENT => SortModeInterface::SORT_ASC,
            SearchService::COLUMN_ENTITY_NAME => SortModeInterface::SORT_ASC,
            SearchService::COLUMN_FIELD_NAME => SortModeInterface::SORT_ASC,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @throws \ReflectionException
     */
    protected function handleQuery(DataQuery $query): DataResults
    {
        /** array<array{id: int, type: string, field: string, content: string, entityname: string, fieldname: string}> $items */
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
            'calculation' => 'fa-fw fa-calculator fa-solid',
            'calculationstate' => 'fa-fw fa-flag fa-regular',
            'task' => 'fa-fw fa-tasks fa-solid',
            'category' => 'fa-fw fa-folder fa-regular',
            'group' => 'fa-fw fa-code-branch fa-solid',
            'product' => 'fa-fw fa-file-alt fa-regular',
            'customer' => 'fa-fw fa-address-card fa-regular',
            default => 'fa-fw fa-file fa-regular',
        };
    }

    /**
     * Update items.
     *
     * @param array<array{id: int, type: string, field: string, content: string, entityname: string, fieldname: string}> $items
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
     * @param array<array{id: int, type: string, field: string, content: string, entityname: string, fieldname: string}> $items
     */
    private function sortItems(array &$items, string $sort, string $order): void
    {
        /** @var array<string, int> */
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
     * @param array{id: int, type: string, field: string, content: string, entityname: string, fieldname: string} $item
     * @param-out  array<"content"|"entityname"|"field"|"fieldname"|"id"|"type", int|string> $item
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
