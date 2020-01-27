<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\DataTables;

use App\DataTables\Columns\DataColumn;
use App\DataTables\Tables\AbstractDataTable;
use App\Interfaces\IEntityVoter;
use App\Security\EntityVoter;
use App\Service\ApplicationService;
use App\Service\SearchService;
use App\Traits\TranslatorTrait;
use App\Utils\Utils;
use DataTables\DataTableQuery;
use DataTables\DataTableResults;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Data table for search data in all entities.
 *
 * @author Laurent Muller
 */
class SearchDataTable extends AbstractDataTable
{
    use TranslatorTrait;

    /**
     * The datatable identifier.
     */
    public const ID = self::class;

    /**
     * The content column name.
     */
    private const COLUMN_CONTENT = SearchService::COLUMN_CONTENT;

    /**
     * The delete granted column name.
     */
    private const COLUMN_DELETE = 'delete_granted';

    /**
     * The edit granted column name.
     */
    private const COLUMN_EDIT = 'edit_granted';

    /**
     * The entity column name.
     */
    private const COLUMN_ENTITY = 'entityName';

    /**
     * The entity column name.
     */
    private const COLUMN_FIELD = 'fieldName';

    /**
     * The show granted column name.
     */
    private const COLUMN_SHOW = 'show_granted';

    /**
     * The authorization checker to get user rights.
     *
     * @var AuthorizationCheckerInterface
     */
    private $checker;

    /**
     * The granted values.
     *
     * @var bool[]
     */
    private $rights = [];

    /**
     * The service to search entities.
     *
     * @var SearchService
     */
    private $service;

    /**
     * Constructor.
     *
     * @param ApplicationService            $application the application to get parameters
     * @param SessionInterface              $session     the session to save/retrieve user parameters
     * @param DataTablesInterface           $datatables  the datatables to handle request
     * @param SearchService                 $service     the service to search entities
     * @param AuthorizationCheckerInterface $checker     the authorization checker to get user rights
     * @param TranslatorInterface           $translator  the service to translate messages
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables, SearchService $service, AuthorizationCheckerInterface $checker, TranslatorInterface $translator)
    {
        parent::__construct($application, $session, $datatables);
        $this->service = $service;
        $this->checker = $checker;
        $this->translator = $translator;
    }

    /**
     * Returns if the given action is granted for one or more entities.
     *
     * @param string $action the action to be tested
     *
     * @return bool true if granted
     */
    public function isActionGranted(string $action): bool
    {
        foreach (EntityVoter::ENTITIES as $entity) {
            if ($this->checker->isGranted($action, $entity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        return [
            DataColumn::hidden(SearchService::COLUMN_ID),
            DataColumn::instance(self::COLUMN_ENTITY)
                ->setTitle('search.fields.entity')
                ->setRender('renderEntityName')
                ->setClassName('cell w-20')
                ->setSearchable(false),
            DataColumn::instance(self::COLUMN_FIELD)
                ->setTitle('search.fields.field')
                ->setClassName('cell w-20')
                ->setSearchable(false),
            DataColumn::instance(SearchService::COLUMN_CONTENT)
                ->setTitle('search.fields.content')
                ->setClassName('cell w-auto')
                ->setSearchable(false)
                ->setDefault(true)
                ->setRawData(true),
            DataColumn::hidden(SearchService::COLUMN_TYPE),
            DataColumn::hidden(SearchService::COLUMN_FIELD),
            DataColumn::hidden(self::COLUMN_SHOW),
            DataColumn::hidden(self::COLUMN_EDIT),
            DataColumn::hidden(self::COLUMN_DELETE),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createDataTableResults(DataTableQuery $query): DataTableResults
    {
        $results = new DataTableResults();

        // search?
        $search = $query->search->value;
        if ($search && \strlen($search) > 1) {
            // search
            //$items = $this->service->search($search, $limit, $offset);
            $items = $this->service->search($search, SearchService::NO_LIMIT);

            // found?
            if (!empty($items)) {
                $limit = $query->length;
                $offset = $query->start;
                $orders = $this->getQueryOrders($query);

                $count = \count($items);
                $results->recordsTotal = $count;
                $results->recordsFiltered = $count;

                // process and sort
                $this->processItems($items);
                $this->sortItems($items, $orders);

                $results->data = \array_slice($items, $offset, $limit);
            }
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnKey(int $key, DataColumn $column)
    {
        return $column->getName();
    }

    /**
     * Gets the columns order for the given query.
     *
     * @param DataTableQuery $query the query to get order
     *
     * @return array the columns order where key it the column name and value the order direction ('asc' or 'desc')
     */
    private function getQueryOrders(DataTableQuery $query): array
    {
        $index = -1;
        $direction = DataColumn::SORT_ASC;
        if (!empty($query->order)) {
            $index = $query->order[0]->column - 1;
            $direction = $query->order[0]->dir;
        }

//         $defaults = [
//             '[' . self::COLUMN_ENTITY . ']' => true,
//             '[' . self::COLUMN_FIELD . ']' => true,
//             '[' . self::COLUMN_CONTENT . ']' => true,
//         ];
//         if (!empty($query->order)) {
//             $index = $query->order[0]->column - 1;
//             $ascending = DataColumn::SORT_ASC === $query->order[0]->dir;
//             $key = array_keys($defaults)[$index];
//             $defaults =  [$key => $ascending] + $defaults;
//         }

        switch ($index) {
            case 0: // entity
                return [
                    self::COLUMN_ENTITY => $direction,
                    self::COLUMN_CONTENT => DataColumn::SORT_ASC,
                    self::COLUMN_FIELD => DataColumn::SORT_ASC,
                ];
            case 1: // field
                return [
                    self::COLUMN_FIELD => $direction,
                    self::COLUMN_CONTENT => DataColumn::SORT_ASC,
                    self::COLUMN_ENTITY => DataColumn::SORT_ASC,
                 ];
            default: // content
                return [
                    self::COLUMN_CONTENT => $direction,
                    self::COLUMN_ENTITY => DataColumn::SORT_ASC,
                    self::COLUMN_FIELD => DataColumn::SORT_ASC,
                 ];
        }
    }

    /**
     * Returns if the given action for the given subject is granted.
     *
     * @param string $action  the action to be tested
     * @param string $subject the subject (the entity name)
     *
     * @return bool true if the action is granted
     */
    private function isGranted(string $action, string $subject): bool
    {
        $key = "{$action}.{$subject}";
        if (!isset($this->rights[$key])) {
            return $this->rights[$key] = $this->checker->isGranted($action, $subject);
        }

        return $this->rights[$key];
    }

    /**
     * Returns if the given subject can be deleted.
     *
     * @param string $subject the subject (entity name)
     *
     * @return bool true if the subject can be deleted
     */
    private function isGrantedDelete(string $subject): bool
    {
        return $this->isGranted(IEntityVoter::ATTRIBUTE_DELETE, $subject);
    }

    /**
     * Returns if the given subject can be edited.
     *
     * @param string $subject the subject (entity name)
     *
     * @return bool true if the subject can be edited
     */
    private function isGrantedEdit(string $subject): bool
    {
        return $this->isGranted(IEntityVoter::ATTRIBUTE_EDIT, $subject);
    }

    /**
     * Returns if the given subject can be displayed.
     *
     * @param string $subject the subject (entity name)
     *
     * @return bool true if the subject can be displayed
     */
    private function isGrantedShow(string $subject): bool
    {
        return $this->isGranted(IEntityVoter::ATTRIBUTE_SHOW, $subject);
    }

    /**
     * Update results.
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
            $item[self::COLUMN_ENTITY] = $this->trans("{$lowerType}.name");
            $item[self::COLUMN_FIELD] = $this->trans("{$lowerType}.fields.{$field}");

            // format content
            $content = $item[SearchService::COLUMN_CONTENT];
            switch ("{$type}.{$field}") {
                case 'Calculation.id':
                    $content = $this->localeId((int) $content);
                    break;
                case 'Calculation.overallTotal':
                case 'Product.price':
                    $content = \number_format((float) $content, 2, '.', '');
                    break;
            }
            $item[SearchService::COLUMN_CONTENT] = $content;

            // key
            //$item['key'] = $lowerType . '.' . $item['id'];

            // set authorizations
            $item[self::COLUMN_SHOW] = $this->isGrantedShow($type);
            $item[self::COLUMN_EDIT] = $this->isGrantedEdit($type);
            $item[self::COLUMN_DELETE] = $this->isGrantedDelete($type);
        }

        // sort
    }

    /**
     * Sorts items.
     *
     * @param array $items  the items to sort
     * @param array $orders the order definitions where key is the field and value is the order ('asc' or 'desc')
     */
    private function sortItems(array &$items, array $orders): void
    {
        // convert orders
        $sorts = [];
        foreach ($orders as $column => $direction) {
            $sorts["[$column]"] = DataColumn::SORT_ASC === $direction;
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
}
