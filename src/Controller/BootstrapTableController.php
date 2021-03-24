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

use AndreaSprega\Bundle\BreadcrumbBundle\Annotation\Breadcrumb;
use App\BootstrapTable\AbstractTable;
use App\BootstrapTable\CalculationBelowTable;
use App\BootstrapTable\CalculationDuplicateTable;
use App\BootstrapTable\CalculationEmptyTable;
use App\BootstrapTable\CalculationStateTable;
use App\BootstrapTable\CalculationTable;
use App\BootstrapTable\CategoryTable;
use App\BootstrapTable\CustomerTable;
use App\BootstrapTable\GlobalMarginTable;
use App\BootstrapTable\GroupTable;
use App\BootstrapTable\LogTable;
use App\BootstrapTable\ProductTable;
use App\BootstrapTable\SearchTable;
use App\BootstrapTable\TaskTable;
use App\BootstrapTable\UserTable;
use App\Interfaces\EntityVoterInterface;
use App\Interfaces\TableInterface;
use App\Repository\CategoryRepository;
use App\Traits\MathTrait;
use App\Util\Utils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controler for the Bootstrap Tables.
 *
 * @author Laurent Muller
 *
 * @Route("/table")
 * @IsGranted("ROLE_USER")
 * @Breadcrumb({
 *     {"label" = "index.title", "route" = "homepage" },
 * })
 */
class BootstrapTableController extends AbstractController
{
    use MathTrait;

    /**
     * Display the calculation below table.
     *
     * @Route("/below", name="table_below")
     * @Breadcrumb({
     *     {"label" = "below.title" }
     * })
     */
    public function below(Request $request, CalculationBelowTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_calculation_below.html.twig');
    }

    /**
     * Save the parameters of the calculation below.
     *
     * @Route("/below/save", name="table_below_save")
     */
    public function belowSave(Request $request, CalculationBelowTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the calculation table.
     *
     * @Route("/calculation", name="table_calculation")
     * @Breadcrumb({
     *     {"label" = "calculation.list.title" }
     * })
     */
    public function calculation(Request $request, CalculationTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_calculation.html.twig');
    }

    /**
     * Save the parameters of the calculation table.
     *
     * @Route("/calculation/save", name="table_calculation_save")
     */
    public function calculationSave(Request $request, CalculationTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the calculation state table.
     *
     * @Route("/calculationstate", name="table_calculationstate")
     * @Breadcrumb({
     *     {"label" = "calculationstate.list.title" }
     * })
     */
    public function calculationState(Request $request, CalculationStateTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_calculation_state.html.twig');
    }

    /**
     * Save the parameters of the calculation state table.
     *
     * @Route("/calculationstate/save", name="table_calculationstate_save")
     */
    public function calculationStateSave(Request $request, CalculationStateTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the category table.
     *
     * @Route("/category", name="table_category")
     * @Breadcrumb({
     *     {"label" = "category.list.title" }
     * })
     */
    public function category(Request $request, CategoryTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_category.html.twig');
    }

    /**
     * Save the parameters of the category table.
     *
     * @Route("/category/save", name="table_category_save")
     */
    public function categorySave(Request $request, CategoryTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the customer table.
     *
     * @Route("/customer", name="table_customer")
     * @Breadcrumb({
     *     {"label" = "customer.list.title" }
     * })
     */
    public function customer(Request $request, CustomerTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_customer.html.twig');
    }

    /**
     * Save customer table parameters.
     *
     * @Route("/customer/save", name="table_customer_save")
     */
    public function customerSave(Request $request, CustomerTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the calculation table with duplicate items.
     *
     * @Route("/duplicate", name="table_duplicate")
     * @Breadcrumb({
     *     {"label" = "duplicate.title" }
     * })
     */
    public function duplicate(Request $request, CalculationDuplicateTable $table): Response
    {
        if (!$request->isXmlHttpRequest() && $table->isEmpty()) {
            $this->warningTrans('duplicate.empty');

            return $this->redirectToHomePage();
        }

        return $this->handleTableRequest($request, $table, 'table/table_calculation_duplicate.html.twig');
    }

    /**
     * Save the parameters of the calculation table with duplicate items.
     *
     * @Route("/duplicate/save", name="table_duplicate_save")
     */
    public function duplicateSave(Request $request, CalculationDuplicateTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the calculation table with empty items.
     *
     * @Route("/empty", name="table_empty")
     * @Breadcrumb({
     *     {"label" = "empty.title" }
     * })
     */
    public function empty(Request $request, CalculationEmptyTable $table): Response
    {
        if (!$request->isXmlHttpRequest() && $table->isEmpty()) {
            $this->warningTrans('empty.empty');

            return $this->redirectToHomePage();
        }

        return $this->handleTableRequest($request, $table, 'table/table_calculation_empty.html.twig');
    }

    /**
     * Save the parameters of the calculation table with empty items.
     *
     * @Route("/empty/save", name="table_empty_save")
     */
    public function emptySave(Request $request, CalculationEmptyTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the global margin table.
     *
     * @Route("/globalmargin", name="table_globalmargin")
     * @Breadcrumb({
     *     {"label" = "globalmargin.list.title" }
     * })
     */
    public function globalMargin(Request $request, GlobalMarginTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_global_margin.html.twig');
    }

    /**
     * Save the parameters of the global margin table.
     *
     * @Route("/globalmargin/save", name="table_globalmargin_save")
     */
    public function globalMarginSave(Request $request, GlobalMarginTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the group table.
     *
     * @Route("/group", name="table_group")
     * @Breadcrumb({
     *     {"label" = "group.list.title" }
     * })
     */
    public function group(Request $request, GroupTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_group.html.twig');
    }

    /**
     * Save the parameters of the group table.
     *
     * @Route("/group/save", name="table_group_save")
     */
    public function groupSave(Request $request, GroupTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the log table.
     *
     * @Route("/log", name="table_log")
     * @Breadcrumb({
     *     {"label" = "log.title" }
     * })
     */
    public function log(Request $request, LogTable $table): Response
    {
        // empty?
        if ($table->isEmpty()) {
            $this->infoTrans('log.list.empty');

            return $this->redirectToHomePage();
        }

        return $this->handleTableRequest($request, $table, 'table/table_log.html.twig');
    }

    /**
     * Save the parameters of the log table.
     *
     * @Route("/log/save", name="table_log_save")
     */
    public function logSave(Request $request, LogTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the product table.
     *
     * @Route("/product", name="table_product")
     * @Breadcrumb({
     *     {"label" = "product.list.title" }
     * })
     */
    public function product(Request $request, ProductTable $table, CategoryRepository $repository): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_product.html.twig');
    }

    /**
     * Save the parameters of the product table.
     *
     * @Route("/product/save", name="table_product_save")
     */
    public function productSave(Request $request, ProductTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the search table.
     *
     * @Route("/search", name="table_search")
     */
    public function search(Request $request, SearchTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_search.html.twig');
    }

    /**
     * Save the parameters of the search table.
     *
     * @Route("/search/save", name="table_search_save")
     * @Breadcrumb({
     *     {"label" = "search.title" }
     * })
     */
    public function searchSave(Request $request, SearchTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the task table.
     *
     * @Route("/task", name="table_task")
     * @Breadcrumb({
     *     {"label" = "task.list.title" }
     * })
     */
    public function task(Request $request, TaskTable $table, CategoryRepository $repository): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_task.html.twig');
    }

    /**
     * Save the parameters of the task table.
     *
     * @Route("/task/save", name="table_task_save")
     */
    public function taskSave(Request $request, TaskTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the user table.
     *
     * @Route("/user", name="table_user")
     * @Breadcrumb({
     *     {"label" = "user.list.title" }
     * })
     */
    public function user(Request $request, UserTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_user.html.twig');
    }

    /**
     * Save the parameters of the user table.
     *
     * @Route("/user/save", name="table_user_save")
     */
    public function userSave(Request $request, UserTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Handle the table request.
     *
     * @param Request       $request  the request to handle
     * @param AbstractTable $table    the table to get parameters for
     * @param string        $template the template to render
     *
     * @return Response the response
     */
    private function handleTableRequest(Request $request, AbstractTable $table, string $template): Response
    {
        // check permission
        if ($name = $table->getEntityClassName()) {
            $this->denyAccessUnlessGranted(EntityVoterInterface::ATTRIBUTE_LIST, $name);
        }

        try {
            // get query and results
            $query = $table->getDataQuery($request);
            $results = $table->processQuery($query);

            // callback?
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse($results->getAjaxResults());
            }

            return $this->render($template, (array) $results);
        } catch (\Exception $e) {
            $status = Response::HTTP_BAD_REQUEST;
            $parameters = [
                'result' => false,
                'status_code' => $status,
                'status_text' => $this->trans('errors.invalid_request'),
                'message' => $this->trans('error_page.description'),
                'exception' => Utils::getExceptionContext($e),
            ];

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse($parameters, $status);
            }

            return $this->render('bundles/TwigBundle/Exception/error.html.twig', $parameters);
        }
    }

    /**
     * Save the parameters of the given table.
     */
    private function saveTableParameters(Request $request, AbstractTable $table): JsonResponse
    {
        $result = $table->saveRequestValue($request, TableInterface::PARAM_CARD, false);

        return new JsonResponse($result);
    }
}
