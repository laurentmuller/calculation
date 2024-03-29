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

namespace App\Controller;

use App\Attribute\Get;
use App\Attribute\GetDelete;
use App\Attribute\GetPost;
use App\Entity\Customer;
use App\Interfaces\RoleInterface;
use App\Report\CustomersReport;
use App\Repository\CustomerRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\CustomersDocument;
use App\Table\CustomerTable;
use App\Table\DataQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for customer entities.
 *
 * @see Customer
 *
 * @template-extends AbstractEntityController<Customer, CustomerRepository>
 */
#[AsController]
#[Route(path: '/customer')]
#[IsGranted(RoleInterface::ROLE_USER)]
class CustomerController extends AbstractEntityController
{
    public function __construct(CustomerRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a customer.
     */
    #[GetPost(path: '/add', name: 'customer_add')]
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new Customer());
    }

    /**
     * Delete a customer.
     */
    #[GetDelete(path: '/delete/{id}', name: 'customer_delete', requirements: self::ID_REQUIREMENT)]
    public function delete(Request $request, Customer $item, LoggerInterface $logger): Response
    {
        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Edit a customer.
     */
    #[GetPost(path: '/edit/{id}', name: 'customer_edit', requirements: self::ID_REQUIREMENT)]
    public function edit(Request $request, Customer $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the customers to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no customer is found
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Get(path: '/excel', name: 'customer_excel')]
    public function excel(CustomerRepository $repository): SpreadsheetResponse
    {
        $entities = $repository->findByNameAndCompany();
        if ([] === $entities) {
            $message = $this->trans('customer.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new CustomersDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export the customers to a PDF document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no customer is found
     */
    #[Get(path: '/pdf', name: 'customer_pdf')]
    public function pdf(Request $request, CustomerRepository $repository): PdfResponse
    {
        $entities = $repository->findByNameAndCompany();
        if ([] === $entities) {
            $message = $this->trans('customer.list.empty');
            throw $this->createNotFoundException($message);
        }
        $grouped = $this->getRequestBoolean($request, 'grouped', true);
        $report = new CustomersReport($this, $entities, $grouped);

        return $this->renderPdfDocument($report);
    }

    /**
     * Show properties of a customer.
     */
    #[Get(path: '/show/{id}', name: 'customer_show', requirements: self::ID_REQUIREMENT)]
    public function show(Customer $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     */
    #[Get(path: '', name: 'customer_table')]
    public function table(
        CustomerTable $table,
        LoggerInterface $logger,
        #[MapQueryString]
        DataQuery $query = new DataQuery(),
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'customer/customer_table.html.twig');
    }
}
