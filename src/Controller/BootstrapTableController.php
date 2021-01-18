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

namespace App\Controller;

use App\BootstrapTable\BootstrapColumn;
use App\BootstrapTable\ProductTable;
use App\Repository\AbstractRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Controler for the Bootstrap Table.
 *
 * @author Laurent Muller
 *
 * @Route("/table")
 * @IsGranted("ROLE_ADMIN")
 */
class BootstrapTableController extends AbstractController
{
    /**
     * The default page size.
     */
    private const PAGE_SIZE = 20;

    // parameter names
    private const PARAM_CARD = 'card';
    private const PARAM_CATEGORY = 'categoryId';
    private const PARAM_ID = 'id';
    private const PARAM_LIMIT = 'limit';
    private const PARAM_OFFSET = 'offset';
    private const PARAM_ORDER = 'order';
    private const PARAM_SEARCH = 'search';
    private const PARAM_SORT = 'sort';

    // session prefixes
    private const PREFIX_PRODUCT = 'table.product';

    /**
     * The where part name of the query builder.
     */
    private const WHERE_PART = 'where';

    /**
     * Display the product table.
     *
     * @Route("/product", name="table_product")
     */
    public function products(Request $request, ProductTable $table, ProductRepository $repository, CategoryRepository $categoryRepository, SerializerInterface $serializer): Response
    {
        /** @var BootstrapColumn[] $columns */
        $columns = $table->getColumns();
        $builder = $repository->createDefaultQueryBuilder();

        // count
        $totalNotFiltered = $filtered = $repository->count([]);

        // search
        $search = $request->get(self::PARAM_SEARCH, '');
        if (\strlen($search) > 0) {
            $expr = new Orx();
            foreach ($columns as $column) {
                if ($column->isSearchable()) {
                    $fields = (array) $repository->getSearchFields($column->getField());
                    foreach ($fields as $field) {
                        $expr->add($field . ' LIKE :' . self::PARAM_SEARCH);
                    }
                }
            }
            if ($expr->count()) {
                $builder->andWhere($expr)
                    ->setParameter(self::PARAM_SEARCH, "%{$search}%");
            }
        }

        // category
        $categoryId = $request->get(self::PARAM_CATEGORY, false);
        if ($categoryId) {
            $field = $repository->getSearchFields('category.id');
            $builder->andWhere($field . '=:' . self::PARAM_CATEGORY)
                ->setParameter(self::PARAM_CATEGORY, $categoryId, Types::INTEGER);
        }

        // count filtered
        if (!empty($builder->getDQLPart(self::WHERE_PART))) {
            $filtered = $this->countFiltered($repository, $builder);
        }

        // sort
        $orderBy = [];
        $sort = $this->getRequestValue($request, self::PREFIX_PRODUCT, self::PARAM_SORT, false);
        $order = $this->getRequestValue($request, self::PREFIX_PRODUCT, self::PARAM_ORDER, Criteria::ASC);
        if ($sort) {
            $fields = (array) $repository->getSortFields($sort);
            foreach ($fields as $field) {
                if (!\array_key_exists($field, $orderBy)) {
                    $orderBy[$field] = $order;
                }
            }
        }

        // add default order if not present
        $defaultColumn = $table->getDefaultColumn();
        $defaultOrder = $defaultColumn->getOrder();
        $fields = (array) $repository->getSortFields($defaultColumn->getField());
        foreach ($fields as $field) {
            if (!\array_key_exists($field, $orderBy)) {
                $orderBy[$field] = $defaultOrder;
            }
        }

        //apply sort
        foreach ($orderBy as $key => $value) {
            $builder->addOrderBy($key, $value);
        }

        // default sort
        if (false === $sort) {
            $sort = $defaultColumn->getField();
        }

        // page
        $offset = (int) $request->get(self::PARAM_OFFSET, 0);
        $limit = (int) $this->getRequestValue($request, self::PREFIX_PRODUCT, self::PARAM_LIMIT, self::PAGE_SIZE);
        $page = 1 + (int) \floor(($offset / $limit));
        $builder->setFirstResult($offset)
            ->setMaxResults($limit);

        // get result and map values
        $items = $builder->getQuery()->getResult();
        $accessor = PropertyAccess::createPropertyAccessor();
        $rows = \array_map(function ($entity) use ($table, $accessor) {
            return $table->mapValues($entity, $accessor);
        }, $items);

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'totalNotFiltered' => $totalNotFiltered,
                'total' => $filtered,
                'rows' => $rows,
            ]);
        }

        $order = \strtolower($order);
        $id = (int) $request->get(self::PARAM_ID, 0);
        $card = \filter_var($this->getRequestValue($request, self::PREFIX_PRODUCT, self::PARAM_CARD, false), FILTER_VALIDATE_BOOLEAN);
        $category = $categoryId ? $categoryRepository->find($categoryId) : null;

        // render
        return $this->render('table/table_product.html.twig', [
            'rows' => $rows,
            'total' => $filtered,
            'totalNotFiltered' => $totalNotFiltered,
            'columns' => $columns,
            'search' => $search,

            'offset' => $offset,
            'limit' => $limit,
            'page' => $page,

            'sort' => $sort,
            'order' => $order,

            'id' => $id,
            'card' => $card,

            'category' => $category,
            'categories' => $categoryRepository->getListCount(),
        ]);
    }

    /**
     * Save products table parameters.
     *
     * @Route("/product/save", name="table_product_save")
     */
    public function productsSave(Request $request): JsonResponse
    {
        $this->getRequestValue($request, self::PREFIX_PRODUCT, self::PARAM_CARD, false);

        return new JsonResponse(true);
    }

    private function countFiltered(AbstractRepository $repository, QueryBuilder $builder): int
    {
        $alias = $builder->getRootAliases()[0];
        $field = $repository->getSingleIdentifierFieldName();
        $select = "COUNT($alias.$field)";

        $cloned = (clone $builder);
        $cloned->select($select);

        return (int) $cloned->getQuery()->getSingleScalarResult();
    }

    /**
     * Gets the request parameter value.
     *
     * @param Request $request the request to get value from
     * @param string  $prefix  the session key prefix
     * @param string  $name    the parameter name
     * @param mixed   $default the default value if not found
     *
     * @return mixed the parameter value
     */
    private function getRequestValue(Request $request, string $prefix, string $name, $default = null)
    {
        $key = "$prefix.$name";
        $session = $request->hasSession() ? $request->getSession() : null;

        if ($session) {
            $default = $session->get($key, $default);
        }

        $value = $request->get($name, $default);

        if ($session) {
            $session->set($key, $value);
        }

        return $value;
    }
}
