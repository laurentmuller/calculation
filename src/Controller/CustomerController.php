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
     * Delete a customer.
     */
    #[GetDelete(path: self::DELETE_PATH, name: self::DELETE_NAME, requirements: self::ID_REQUIREMENT)]
    public function delete(Request $request, Customer $item, LoggerInterface $logger): Response
    {
        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Add or edit a customer.
     */
    #[GetPost(path: self::ADD_PATH, name: self::ADD_NAME)]
    #[GetPost(path: self::EDIT_PATH, name: self::EDIT_NAME, requirements: self::ID_REQUIREMENT)]
    public function edit(Request $request, ?Customer $item): Response
    {
        return $this->editEntity($request, $item ?? new Customer());
    }

    /**
     * Export the customers to a Spreadsheet document.
     */
    #[Get(path: self::EXCEL_PATH, name: self::EXCEL_NAME)]
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
    #[Get(path: self::INDEX_PATH, name: self::INDEX_NAME)]
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
     */
    #[Get(path: self::PDF_PATH, name: self::PDF_NAME)]
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
    #[Get(path: self::SHOW_PATH, name: self::SHOW_NAME, requirements: self::ID_REQUIREMENT)]
    public function show(Customer $item): Response
    {
        return $this->showEntity($item);
    }
}
