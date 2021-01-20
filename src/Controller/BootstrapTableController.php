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

use App\BootstrapTable\AbstractBootstrapEntityTable;
use App\BootstrapTable\AbstractBootstrapTable;
use App\BootstrapTable\CalculationTable;
use App\BootstrapTable\CustomerTable;
use App\BootstrapTable\ProductTable;
use App\Repository\CalculationStateRepository;
use App\Repository\CategoryRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Annotation\Route;

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
     * The where part name of the query builder.
     */
    private const WHERE_PART = 'where';

    /**
     * Display the calculation table.
     *
     * @Route("/calculation", name="table_calculation")
     */
    public function calculation(Request $request, CalculationTable $table, CalculationStateRepository $repository): Response
    {
        // get parameters
        $parameters = $this->handleTableRequest($request, $table);

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($parameters);
        }

        // state
        $parameters['state'] = $table->getCalculationState($repository);
        $parameters['states'] = $repository->getListCount();

        // render
        return $this->render('table/table_calculation.html.twig', $parameters);
    }

    /**
     * Save calculation table parameters.
     *
     * @Route("/calculation/save", name="table_calculation_save")
     */
    public function calculationSave(Request $request, CalculationTable $table): JsonResponse
    {
        $result = $table->saveRequestValue($request, ProductTable::PARAM_CARD, false);

        return new JsonResponse($result);
    }

    /**
     * Display the customer table.
     *
     * @Route("/customer", name="table_customer")
     */
    public function customer(Request $request, CustomerTable $table): Response
    {
        // get parameters
        $parameters = $this->handleTableRequest($request, $table);

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($parameters);
        }

        // render
        return $this->render('table/table_customer.html.twig', $parameters);
    }

    /**
     * Save customer table parameters.
     *
     * @Route("/customer/save", name="table_customer_save")
     */
    public function customerSave(Request $request, CustomerTable $table): JsonResponse
    {
        $result = $table->saveRequestValue($request, AbstractBootstrapTable::PARAM_CARD, false);

        return new JsonResponse($result);
    }

    /**
     * Display the product table.
     *
     * @Route("/product", name="table_product")
     */
    public function product(Request $request, ProductTable $table, CategoryRepository $repository): Response
    {
        // get parameters
        $parameters = $this->handleTableRequest($request, $table);

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($parameters);
        }

        // category
        $parameters['category'] = $table->getCategory($repository);
        $parameters['categories'] = $repository->getListCount();

        // render
        return $this->render('table/table_product.html.twig', $parameters);
    }

    /**
     * Save product table parameters.
     *
     * @Route("/product/save", name="table_product_save")
     */
    public function productSave(Request $request, ProductTable $table): JsonResponse
    {
        $result = $table->saveRequestValue($request, ProductTable::PARAM_CARD, false);

        return new JsonResponse($result);
    }

    /**
     * Handle the table request.
     *
     * @param Request                      $request the request
     * @param AbstractBootstrapEntityTable $table   the table to get values for
     *
     * @return array the parameters
     */
    private function handleTableRequest(Request $request, AbstractBootstrapEntityTable $table): array
    {
        // builder
        $builder = $table->createDefaultQueryBuilder();

        // count
        $totalNotFiltered = $filtered = $table->count();

        // search
        $search = $table->addSearch($request, $builder);

        // count filtered
        if (!empty($builder->getDQLPart(self::WHERE_PART))) {
            $filtered = $table->countFiltered($builder);
        }

        // sort
        [$sort, $order] = $table->addOrderBy($request, $builder);

        // limit
        [$offset, $limit] = $table->addLimit($request, $builder);
        $page = 1 + (int) \floor(($offset / $limit));

        // get result and map values
        $items = $builder->getQuery()->getResult();
        $accessor = PropertyAccess::createPropertyAccessor();
        $rows = \array_map(function ($entity) use ($table, $accessor) {
            return $table->mapValues($entity, $accessor);
        }, $items);

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return [
                'totalNotFiltered' => $totalNotFiltered,
                'total' => $filtered,
                'rows' => $rows,
            ];
        }

        $id = (int) $request->get(AbstractBootstrapTable::PARAM_ID, 0);
        $card = (bool) \filter_var($table->getRequestValue($request, AbstractBootstrapTable::PARAM_CARD, false), FILTER_VALIDATE_BOOLEAN);

        // render
        return [
            'columns' => $table->getColumns(),
            'rows' => $rows,
            'id' => $id,

            'totalNotFiltered' => $totalNotFiltered,
            'total' => $filtered,

            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'search' => $search,
            'card' => $card,

            'sort' => $sort,
            'order' => $order,
        ];
    }
}
