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

use App\DataTable\CustomerDataTable;
use App\Entity\AbstractEntity;
use App\Entity\Customer;
use App\Form\Customer\CustomerType;
use App\Report\CustomersReport;
use App\Repository\CustomerRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\CustomersDocument;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for customer entities.
 *
 * @see \App\Entity\Customer
 *
 * @author Laurent Muller
 *
 * @Route("/customer")
 * @IsGranted("ROLE_USER")
 * @Breadcrumb({
 *     {"label" = "index.title", "route" = "homepage"},
 *     {"label" = "customer.list.title", "route" = "table_customer", "params" = {
 *         "id" = "$params.[id]",
 *         "search" = "$params.[search]",
 *         "sort" = "$params.[sort]",
 *         "order" = "$params.[order]",
 *         "offset" = "$params.[offset]",
 *         "limit" = "$params.[limit]",
 *         "view" = "$params.[view]"
 *     }}
 * })
 * @template-extends AbstractEntityController<Customer>
 */
class CustomerController extends AbstractEntityController
{
    /**
     * Constructor.
     */
    public function __construct(CustomerRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a customer.
     *
     * @Route("/add", name="customer_add")
     * @Breadcrumb({
     *     {"label" = "breadcrumb.add"}
     * })
     */
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new Customer());
    }

    /**
     * List the customers.
     *
     * @Route("/card", name="customer_card")
     */
    public function card(Request $request): Response
    {
        $sortedFields = [
            ['name' => CustomerRepository::NAME_COMPANY_FIELD, 'label' => 'customer.fields.nameAndCompany'],
            ['name' => CustomerRepository::ZIP_CITY_FIELD, 'label' => 'customer.fields.zipCity'],
        ];

        return $this->renderCard($request, CustomerRepository::NAME_COMPANY_FIELD, Criteria::ASC, $sortedFields);
    }

    /**
     * Delete a customer.
     *
     * @Route("/delete/{id}", name="customer_delete", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.delete" }
     * })
     */
    public function delete(Request $request, Customer $item): Response
    {
        $parameters = [
            'title' => 'customer.delete.title',
            'message' => 'customer.delete.message',
            'success' => 'customer.delete.success',
            'failure' => 'customer.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $parameters);
    }

    /**
     * Edit a customer.
     *
     * @Route("/edit/{id}", name="customer_edit", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.edit" }
     * })
     */
    public function edit(Request $request, Customer $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the customers to a Spreadsheet document.
     *
     * @Route("/excel", name="customer_excel")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no customer is found
     */
    public function excel(CustomerRepository $repository): SpreadsheetResponse
    {
        /** @var Customer[] $entities */
        $entities = $repository->findAllByNameAndCompany();
        if (empty($entities)) {
            $message = $this->trans('customer.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new CustomersDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export the customers to a PDF document.
     *
     * @Route("/pdf", name="customer_pdf")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no customer is found
     */
    public function pdf(Request $request, CustomerRepository $repository): PdfResponse
    {
        /** @var Customer[] $entities */
        $entities = $repository->findAllByNameAndCompany();
        if (empty($entities)) {
            $message = $this->trans('customer.list.empty');
            throw $this->createNotFoundException($message);
        }

        $grouped = (bool) $request->get('grouped', true);
        $report = new CustomersReport($this, $entities, $grouped);

        return $this->renderPdfDocument($report);
    }

    /**
     * Show properties of a customer.
     *
     * @Route("/show/{id}", name="customer_show", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.property" }
     * })
     */
    public function show(Customer $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("", name="customer_table")
     */
    public function table(Request $request, CustomerDataTable $table): Response
    {
        return $this->renderTable($request, $table);
    }

    /**
     * {@inheritdoc}
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        // update parameters
        $parameters['success'] = $item->isNew() ? 'customer.add.success' : 'customer.edit.success';

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditFormType(): string
    {
        return CustomerType::class;
    }
}
