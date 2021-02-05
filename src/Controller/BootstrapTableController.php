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
use App\Repository\CalculationStateRepository;
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use App\Traits\MathTrait;
use App\Util\FormatUtils;
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
 */
class BootstrapTableController extends AbstractController
{
    use MathTrait;

    /**
     * Display the calculation below table.
     *
     * @Route("/below", name="table_below")
     */
    public function below(Request $request, CalculationBelowTable $table): Response
    {
        $parameters = function () {
            $margin = $this->getApplication()->getMinMargin();

            return [
                'min_margin' => $margin,
                'min_margin_text' => $this->trans('calculation.list.margin_below', ['%minimum%' => FormatUtils::formatPercent($margin)]),
            ];
        };

        return $this->handleTableRequest($request, $table, 'table/table_calculation_below.html.twig', $parameters);
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
     */
    public function calculation(Request $request, CalculationTable $table, CalculationStateRepository $repository): Response
    {
        $parameters = function () use ($table, $repository) {
            $margin = $this->getApplication()->getMinMargin();

            return [
                'state' => $table->getCalculationState($repository),
                'states' => $repository->getListCount(),
                'min_margin' => $margin,
                'min_margin_text' => $this->trans('calculation.list.margin_below', ['%minimum%' => FormatUtils::formatPercent($margin)]),
            ];
        };

        return $this->handleTableRequest($request, $table, 'table/table_calculation.html.twig', $parameters);
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
     */
    public function category(Request $request, CategoryTable $table, GroupRepository $repository): Response
    {
        $parameters = function () use ($table, $repository) {
            return [
                'group' => $table->getGroup($repository),
                'groups' => $repository->getListCount(),
            ];
        };

        return $this->handleTableRequest($request, $table, 'table/table_category.html.twig', $parameters);
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
     */
    public function duplicate(Request $request, CalculationDuplicateTable $table): Response
    {
        if ($table->isEmpty($request)) {
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
     */
    public function empty(Request $request, CalculationEmptyTable $table): Response
    {
        if ($table->isEmpty($request)) {
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
     */
    public function product(Request $request, ProductTable $table, CategoryRepository $repository): Response
    {
        $parameters = function () use ($table, $repository) {
            return [
                'category' => $table->getCategory($repository),
                'categories' => $repository->getListCount(),
            ];
        };

        return $this->handleTableRequest($request, $table, 'table/table_product.html.twig', $parameters);
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
     */
    public function searchSave(Request $request, SearchTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the task table.
     *
     * @Route("/task", name="table_task")
     */
    public function task(Request $request, TaskTable $table, CategoryRepository $repository): Response
    {
        $parameters = function () use ($table, $repository) {
            return [
                'category' => $table->getCategory($repository),
                'categories' => $repository->getListTaskCount(),
            ];
        };

        return $this->handleTableRequest($request, $table, 'table/table_task.html.twig', $parameters);
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
     * @param Request       $request              the request to handle
     * @param AbstractTable $table                the table
     * @param string        $template             the template to render
     * @param callable      $additionalParameters the function used to merge parameters
     *
     * @return Response the response
     */
    private function handleTableRequest(Request $request, AbstractTable $table, string $template, ?callable $additionalParameters = null): Response
    {
        // check permission
        $name = $table->getEntityClassName();
        $this->denyAccessUnlessGranted(EntityVoterInterface::ATTRIBUTE_LIST, $name);

        try {
            $status = Response::HTTP_OK;
            $parameters = $table->handleRequest($request);
        } catch (\Exception $e) {
            $status = Response::HTTP_BAD_REQUEST;
            $parameters = [
                'result' => false,
                'status_code' => $status,
                'status_text' => $this->trans('errors.invalid_request'),
                'message' => $this->trans('error_page.description'),
                'exception' => Utils::getExceptionContext($e),
            ];
        }

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($parameters, $status);
        }

        // ok?
        if (Response::HTTP_OK !== $status) {
            return $this->render('bundles/TwigBundle/Exception/error.html.twig', $parameters);
        }

        // additional parameters?
        if (\is_callable($additionalParameters)) {
            $parameters = \array_merge($parameters, \call_user_func($additionalParameters));
        }

        // render
        return $this->render($template, $parameters);
    }

    /**
     * Save the parameters of the given table.
     */
    private function saveTableParameters(Request $request, AbstractTable $table): JsonResponse
    {
        $result = $table->saveRequestValue($request, AbstractTable::PARAM_CARD, false);

        return new JsonResponse($result);
    }
}
