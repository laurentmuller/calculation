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
use App\Traits\CheckerTrait;
use App\Traits\TranslatorTrait;
use App\Util\FileUtils;
use App\Util\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The search table.
 *
 * @author Laurent Muller
 */
class SearchTable extends AbstractTable
{
    use CheckerTrait;
    use TranslatorTrait;

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
    public function __construct(private readonly SearchService $service, AuthorizationCheckerInterface $checker, TranslatorInterface $translator)
    {
        $this->setChecker($checker);
        $this->setTranslator($translator);
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
     */
    protected function handleQuery(DataQuery $query): DataResults
    {
        $results = parent::handleQuery($query);

        // get parameters
        $search = $query->search;
        $entity = (string) $query->customData[self::PARAM_ENTITY];

        // search
        $items = \strlen($search) > 1 ? $this->service->search($search, $entity, SearchService::NO_LIMIT) : [];

        // total
        $results->totalNotFiltered = $results->filtered = \count($items);

        // found?
        if (0 !== $results->totalNotFiltered) {
            // process
            $this->processItems($items);

            // sort
            $this->sortItems($items, $query->sort, $query->order);

            // limit
            $items = \array_slice($items, $query->offset, $query->limit);

            // update entity name (icon)
            foreach ($items as &$item) {
                $this->updateItem($item);
            }
        } else {
            $items = [];
        }
        $results->rows = $items;

        // ajax?
        if (!$query->callback) {
            $results->customData = [
                'entity' => $entity,
                'entities' => $this->service->getEntities(),
            ];
            $results->addParameter(self::PARAM_ENTITY, $entity);
        }

        return $results;
    }

    /**
     * Update items.
     *
     * @param array<array<string, mixed>> $items
     */
    private function processItems(array &$items): void
    {
        foreach ($items as &$item) {
            $type = (string) $item[SearchService::COLUMN_TYPE];
            $field = (string) $item[SearchService::COLUMN_FIELD];
            $lowerType = \strtolower($type);

            $item[SearchService::COLUMN_ACTION] = (int) $item['id'];
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
     * @param array<array<string, mixed>> $items
     */
    private function sortItems(array &$items, string $sort, string $order): void
    {
        // create sort
        $sorts = ["[$sort]" => self::SORT_ASC === $order];
        foreach (self::SORT_COLUMNS as $field) {
            $field = "[$field]";
            if (!\array_key_exists($field, $sorts)) {
                $sorts[$field] = true;
            }
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        \uasort($items, function (array $a, array $b) use ($sorts, $accessor) {
            foreach ($sorts as $field => $ascending) {
                $result = Utils::compare($a, $b, $field, $accessor, $ascending);
                if (0 !== $result) {
                    return $result;
                }
            }

            return 0;
        });
    }

    /**
     * Update the item.
     *
     * @param array<string, mixed> $item
     */
    private function updateItem(array &$item): void
    {
        $name = (string) $item[SearchService::COLUMN_ENTITY_NAME];
        $type = \strtolower((string) $item[SearchService::COLUMN_TYPE]);

        $icon = 'file far';
        switch ($type) {
            case 'calculation':
                $icon = 'calculator fas';
                break;
            case 'calculationstate':
                $icon = 'flag far';
                break;
            case 'task':
                $icon = 'tasks fas';
                break;
            case 'category':
                $icon = 'folder far';
                break;
            case 'group':
                $icon = 'code-branch fas';
                break;
            case 'product':
                $icon = 'file-alt far';
                break;
            case 'customer':
                $icon = 'address-card far';
                break;
        }
        $item[SearchService::COLUMN_ENTITY_NAME] = \sprintf('<i class="fa-fw fa-%s" aria-hidden="true"></i>&nbsp;%s', $icon, $name);
        $item[SearchService::COLUMN_TYPE] = $type;
        unset($item[SearchService::COLUMN_FIELD]);
    }
}
