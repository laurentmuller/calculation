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

use App\BootstrapTable\AbstractBootstrapTable;
use App\BootstrapTable\CalculationStateTable;
use App\BootstrapTable\CalculationTable;
use App\BootstrapTable\CategoryTable;
use App\BootstrapTable\CustomerTable;
use App\BootstrapTable\LogTable;
use App\BootstrapTable\ProductTable;
use App\BootstrapTable\UserTable;
use App\Interfaces\EntityVoterInterface;
use App\Repository\CalculationStateRepository;
use App\Repository\CategoryRepository;
use App\Traits\MathTrait;
use App\Util\FormatUtils;
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
 * @IsGranted("ROLE_ADMIN")
 */
class BootstrapTableController extends AbstractController
{
    use MathTrait;

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

        // margin
        $margin = $this->getApplication()->getMinMargin();
        $parameters['min_margin'] = $margin;
        $parameters['min_margin_text'] = $this->trans('calculation.list.margin_below', ['%minimum%' => FormatUtils::formatPercent($margin)]);

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
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the calculation state table.
     *
     * @Route("/calculationstate", name="table_calculationstate")
     */
    public function calculationState(Request $request, CalculationStateTable $table): Response
    {
        // get parameters
        $parameters = $this->handleTableRequest($request, $table);

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($parameters);
        }

        // render
        return $this->render('table/table_calculation_state.html.twig', $parameters);
    }

    /**
     * Save calculation state table parameters.
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
    public function category(Request $request, CategoryTable $table): Response
    {
        // get parameters
        $parameters = $this->handleTableRequest($request, $table);

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($parameters);
        }

        // render
        return $this->render('table/table_category.html.twig', $parameters);
    }

    /**
     * Save category table parameters.
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
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the log table.
     *
     * @Route("/log", name="table_log")
     */
    public function log(Request $request, LogTable $table): Response
    {
        // get parameters
        $parameters = $this->handleTableRequest($request, $table);
        if (empty($parameters)) {
            $this->infoTrans('log.list.empty');

            return $this->redirectToHomePage();
        }

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($parameters);
        }

        // channels and levels
        $parameters['channels'] = $table->getChannels();
        $parameters['channel'] = $table->getChannel();
        $parameters['levels'] = $table->getLevels();
        $parameters['level'] = $table->getLevel();
        $parameters['file'] = $table->getFileName();

        // render
        return $this->render('table/table_log.html.twig', $parameters);
    }

    /**
     * Save log table parameters.
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
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Display the user table.
     *
     * @Route("/user", name="table_user")
     */
    public function user(Request $request, UserTable $table): Response
    {
        // get parameters
        $parameters = $this->handleTableRequest($request, $table);

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($parameters);
        }

        // render
        return $this->render('table/table_user.html.twig', $parameters);
    }

    /**
     * Save user table parameters.
     *
     * @Route("/user/save", name="table_user_save")
     */
    public function userSave(Request $request, UserTable $table): JsonResponse
    {
        return $this->saveTableParameters($request, $table);
    }

    /**
     * Handle the table request.
     */
    private function handleTableRequest(Request $request, AbstractBootstrapTable $table): array
    {
        // check permission
        $name = $table->getEntityClassName();
        $this->denyAccessUnlessGranted(EntityVoterInterface::ATTRIBUTE_LIST, $name);

        return $table->handleRequest($request);
    }

    /**
     * Save the table parameters.
     */
    private function saveTableParameters(Request $request, AbstractBootstrapTable $table): JsonResponse
    {
        $result = $table->saveRequestValue($request, AbstractBootstrapTable::PARAM_CARD, false);

        return new JsonResponse($result);
    }
}
