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
use App\Resolver\DataQueryValueResolver;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\CustomersDocument;
use App\Table\CustomerTable;
use App\Table\DataQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
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
#[Route(path: '/customer', name: 'customer_')]
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
    #[GetPost(path: '/add', name: 'add')]
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new Customer());
    }

    /**
     * Delete a customer.
     */
    #[GetDelete(path: '/delete/{id}', name: 'delete', requirements: self::ID_REQUIREMENT)]
    public function delete(Request $request, Customer $item, LoggerInterface $logger): Response
    {
        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Edit a customer.
     */
    #[GetPost(path: '/edit/{id}', name: 'edit', requirements: self::ID_REQUIREMENT)]
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
    #[Get(path: '/excel', name: 'excel')]
    public function excel(CustomerRepository $repository): SpreadsheetResponse
    {
        $entities = $repository->findByNameAndCompany();
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('customer.list.empty');
        }
        $doc = new CustomersDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[Get(path: '', name: 'index')]
    public function index(
        CustomerTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery(),
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'customer/customer_table.html.twig');
    }

    /**
     * Export the customers to a PDF document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no customer is found
     */
    #[Get(path: '/pdf', name: 'pdf')]
    public function pdf(Request $request, CustomerRepository $repository): PdfResponse
    {
        $entities = $repository->findByNameAndCompany();
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('customer.list.empty');
        }
        $grouped = $this->getRequestBoolean($request, 'grouped', true);
        $report = new CustomersReport($this, $entities, $grouped);

        return $this->renderPdfDocument($report);
    }

    /**
     * Show properties of a customer.
     */
    #[Get(path: '/show/{id}', name: 'show', requirements: self::ID_REQUIREMENT)]
    public function show(Customer $item): Response
    {
        return $this->showEntity($item);
    }
}
