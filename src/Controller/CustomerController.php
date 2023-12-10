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

use App\Entity\Customer;
use App\Form\Customer\CustomerType;
use App\Interfaces\RoleInterface;
use App\Report\CustomersReport;
use App\Repository\CustomerRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\CustomersDocument;
use App\Table\CustomerTable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
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
    #[Route(path: '/add', name: 'customer_add', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new Customer());
    }

    /**
     * Delete a customer.
     */
    #[Route(path: '/delete/{id}', name: 'customer_delete', requirements: ['id' => Requirement::DIGITS], methods: [Request::METHOD_GET, Request::METHOD_DELETE])]
    public function delete(Request $request, Customer $item, LoggerInterface $logger): Response
    {
        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Edit a customer.
     */
    #[Route(path: '/edit/{id}', name: 'customer_edit', requirements: ['id' => Requirement::DIGITS], methods: [Request::METHOD_GET, Request::METHOD_POST])]
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
    #[Route(path: '/excel', name: 'customer_excel', methods: Request::METHOD_GET)]
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
    #[Route(path: '/pdf', name: 'customer_pdf', methods: Request::METHOD_GET)]
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
    #[Route(path: '/show/{id}', name: 'customer_show', requirements: ['id' => Requirement::DIGITS], methods: Request::METHOD_GET)]
    public function show(Customer $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     */
    #[Route(path: '', name: 'customer_table', methods: Request::METHOD_GET)]
    public function table(Request $request, CustomerTable $table, LoggerInterface $logger): Response
    {
        return $this->handleTableRequest(
            $request,
            $table,
            $logger,
            'customer/customer_table.html.twig'
        );
    }

    protected function getEditFormType(): string
    {
        return CustomerType::class;
    }
}
