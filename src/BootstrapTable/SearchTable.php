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

namespace App\BootstrapTable;

use App\Interfaces\TableInterface;
use App\Service\SearchService;
use App\Traits\CheckerTrait;
use App\Traits\TranslatorTrait;
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
    public const PARAM_ENTITY = 'entity';

    /**
     * The type parameter name.
     */
    public const PARAM_TYPE = 'type';

    /**
     * The action column name.
     */
    private const COLUMN_ACTION = 'action';

    /**
     * The content column name.
     */
    private const COLUMN_CONTENT = SearchService::COLUMN_CONTENT;

    /**
     * The entity column name.
     */
    private const COLUMN_ENTITY_NAME = 'entityName';

    /**
     * The field column name.
     */
    private const COLUMN_FIELD_NAME = 'fieldName';

    /**
     * The delete granted column name.
     */
    private const COLUMN_GRANTED_DELETE = 'deleteGranted';

    /**
     * The edit granted column name.
     */
    private const COLUMN_GRANTED_EDIT = 'editGranted';

    /**
     * The show granted column name.
     */
    private const COLUMN_GRANTED_SHOW = 'showGranted';

    /**
     * The default sort columns order.
     */
    private const SORT_COLUMNS = [
        self::COLUMN_CONTENT,
        self::COLUMN_ENTITY_NAME,
        self::COLUMN_FIELD_NAME,
    ];

    private SearchService $service;

    /**
     * Constructor.
     */
    public function __construct(SearchService $service, AuthorizationCheckerInterface $checker, TranslatorInterface $translator)
    {
        $this->service = $service;
        $this->checker = $checker;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function getDataQuery(Request $request): DataQuery
    {
        $query = parent::getDataQuery($request);
        $query->addCustomData(self::PARAM_ENTITY, (string) $request->get(self::PARAM_ENTITY, ''));

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
        return __DIR__ . '/Definition/search.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return [
            self::COLUMN_CONTENT => self::SORT_ASC,
            self::COLUMN_ENTITY_NAME => self::SORT_ASC,
            self::COLUMN_FIELD_NAME => self::SORT_ASC,
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
        $entity = $query->customData[self::PARAM_ENTITY];

        // search
        if (\strlen($search) > 1) {
            $items = $this->service->search($search, $entity, SearchService::NO_LIMIT);
        } else {
            $items = [];
        }

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
        }

        return $results;
    }

    /**
     * Update items.
     *
     * @param array $items the items to update
     */
    private function processItems(array &$items): void
    {
        foreach ($items as &$item) {
            $type = $item[SearchService::COLUMN_TYPE];
            $field = $item[SearchService::COLUMN_FIELD];

            // translate entity and field names
            $lowerType = \strtolower($type);
            $entity = $this->trans("{$lowerType}.name");
            $item[self::COLUMN_ACTION] = $item['id'];
            $item[self::COLUMN_ENTITY_NAME] = $entity;
            $item[self::COLUMN_FIELD_NAME] = $this->trans("{$lowerType}.fields.{$field}");

            // format content
            $content = $item[SearchService::COLUMN_CONTENT];
            switch ("{$type}.{$field}") {
                case 'Calculation.id':
                    $content = $this->formatId((int) $content);
                    break;
                case 'Calculation.overallTotal':
                case 'Product.price':
                    $content = $this->formatAmount((float) $content);
                    break;
            }
            $item[SearchService::COLUMN_CONTENT] = $content;

            // set authorizations
            $item[self::COLUMN_GRANTED_SHOW] = $this->isGrantedShow($type);
            $item[self::COLUMN_GRANTED_EDIT] = $this->isGrantedEdit($type);
            $item[self::COLUMN_GRANTED_DELETE] = $this->isGrantedDelete($type);
        }
    }

    /**
     * Sorts items.
     *
     * @param array  $items the items to sort
     * @param string $sort  the sorted column
     * @param string $order the sorted direction ('asc' or 'desc')
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
     */
    private function updateItem(array &$item): void
    {
        $name = $item[self::COLUMN_ENTITY_NAME];
        $type = \strtolower($item[SearchService::COLUMN_TYPE]);

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
        $item[self::COLUMN_ENTITY_NAME] = \sprintf('<i class="fa-fw fa-%s" aria-hidden="true"></i>&nbsp;%s', $icon, $name);
        $item[SearchService::COLUMN_TYPE] = $type;
        unset($item[SearchService::COLUMN_FIELD]);
    }
}
