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
use App\BootstrapTable\DataResults;
use App\BootstrapTable\GlobalMarginTable;
use App\BootstrapTable\GroupTable;
use App\BootstrapTable\LogTable;
use App\BootstrapTable\ProductTable;
use App\BootstrapTable\SearchTable;
use App\BootstrapTable\TaskTable;
use App\BootstrapTable\UserTable;
use App\Interfaces\EntityVoterInterface;
use App\Interfaces\TableInterface;
use App\Traits\MathTrait;
use App\Util\Utils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controler for the Bootstrap tables.
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
     * @IsGranted("ROLE_ADMIN")
     * @Breadcrumb({
     *     {"label" = "below.title" }
     * })
     */
    public function below(Request $request, CalculationBelowTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_calculation_below.html.twig');
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
     * Display the calculation table with duplicate items.
     *
     * @Route("/duplicate", name="table_duplicate")
     * @IsGranted("ROLE_ADMIN")
     * @Breadcrumb({
     *     {"label" = "duplicate.title" }
     * })
     */
    public function duplicate(Request $request, CalculationDuplicateTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_calculation_duplicate.html.twig');
    }

    /**
     * Display the calculation table with empty items.
     *
     * @Route("/empty", name="table_empty")
     * @IsGranted("ROLE_ADMIN")
     * @Breadcrumb({
     *     {"label" = "empty.title" }
     * })
     */
    public function empty(Request $request, CalculationEmptyTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_calculation_empty.html.twig');
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
     * Display the log table.
     *
     * @Route("/log", name="table_log")
     * @IsGranted("ROLE_ADMIN")
     * @Breadcrumb({
     *     {"label" = "log.title" }
     * })
     */
    public function log(Request $request, LogTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_log.html.twig');
    }

    /**
     * Display the product table.
     *
     * @Route("/product", name="table_product")
     * @Breadcrumb({
     *     {"label" = "product.list.title" }
     * })
     */
    public function product(Request $request, ProductTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_product.html.twig');
    }

    /**
     * Save table parameters.
     *
     * @Route("/save", name="table_save")
     */
    public function save(Request $request): JsonResponse
    {
        $view = (string) $this->getRequestString($request, TableInterface::PARAM_VIEW, TableInterface::VIEW_TABLE);
        $limit = AbstractTable::getDefaultPageSize($view);

        $response = $this->json(true);
        $this->setCookie($response, TableInterface::PARAM_VIEW, $view);
        $this->setCookie($response, TableInterface::PARAM_LIMIT, $limit, $view);

        return $response;
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
     * Display the task table.
     *
     * @Route("/task", name="table_task")
     * @Breadcrumb({
     *     {"label" = "task.list.title" }
     * })
     */
    public function task(Request $request, TaskTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'table/table_task.html.twig');
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

    private function clearCookie(Response $response, string $key, string $prefix = ''): void
    {
        $name = $this->getCookieName($key, $prefix);
        $response->headers->clearCookie($name);
    }

    private function getCookieName(string $key, string $prefix = ''): string
    {
        return '' === $prefix ? \strtoupper($key) : \strtoupper("{$prefix}_{$key}");
    }

    private function handleTableRequest(Request $request, AbstractTable $table, string $template): Response
    {
        // check permission
        if ($subject = $table->getEntityClassName()) {
            $this->denyAccessUnlessGranted(EntityVoterInterface::ATTRIBUTE_LIST, $subject);
        }

        // update request parameters
        $view = $this->updateRequest($request, TableInterface::PARAM_VIEW, TableInterface::VIEW_TABLE);
        if (\is_string($view)) {
            $this->updateRequest($request, TableInterface::PARAM_LIMIT, AbstractTable::getDefaultPageSize($view), $view);
        }

        // check empty
        if ($emptyMessage = $table->checkEmpty()) {
            $this->infoTrans($emptyMessage);

            return $this->redirectToHomePage();
        }

        try {
            // get query and results
            $query = $table->getDataQuery($request);
            $results = $table->processQuery($query);

            // callback?
            if ($query->callback) {
                $response = $this->json($results);
            } else {
                // empty?
                if (0 === $results->totalNotFiltered && !$table->isEmptyAllowed()) {
                    $this->infoTrans($table->getEmptyMessage());

                    return $this->redirectToHomePage();
                }

                // generate
                $response = $this->renderForm($template, (array) $results);
            }

            // save results
            $this->saveCookie($response, $results, TableInterface::PARAM_VIEW, TableInterface::VIEW_TABLE);
            $this->saveCookie($response, $results, TableInterface::PARAM_LIMIT, TableInterface::PAGE_SIZE, $query->view);

            return $response;
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
                return $this->json($parameters, $status);
            }

            return $this->renderForm('bundles/TwigBundle/Exception/error.html.twig', $parameters);
        }
    }

    /**
     * @param string|int|float|bool|null $default the default value if the result parameter is null
     */
    private function saveCookie(Response $response, DataResults $results, string $key, $default = null, string $prefix = '', string $modify = '+1 year'): void
    {
        /** @psalm-var string|int|float|bool|array|null $value */
        $value = $results->getParams($key, $default);
        if (null !== $value) {
            $this->setCookie($response, $key, $value, $prefix, $modify);
        } else {
            $this->clearCookie($response, $key, $prefix);
        }
    }

    /**
     * @param mixed $value the cookie value
     */
    private function setCookie(Response $response, string $key, $value, string $prefix = '', string $modify = '+1 year'): void
    {
        $name = $this->getCookieName($key, $prefix);
        $expire = (new \DateTime())->modify($modify);
        $cookie = new Cookie($name, (string) $value, $expire);
        $response->headers->setCookie($cookie);
    }

    /**
     * @param string|int|float|bool|null $default the default value if the input key does not exist
     *
     * @return string|int|float|bool|null the request value, the cookie value or the default value
     * @psalm-suppress InvalidScalarArgument
     */
    private function updateRequest(Request $request, string $key, $default = null, string $prefix = '')
    {
        $input = Utils::getRequestInputBag($request);
        $value = $input->get($key);
        if (null === $value) {
            $cookies = $request->cookies;
            $name = $this->getCookieName($key, $prefix);
            // @phpstan-ignore-next-line
            $value = $cookies->get($name, $default);
            if (null !== $value) {
                $input->set($key, $value);
            }
        }

        return $value;
    }
}
