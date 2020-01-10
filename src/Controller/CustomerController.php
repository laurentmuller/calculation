<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Controller;

use App\DataTables\CustomerDataTable;
use App\Entity\Customer;
use App\Form\CustomerType;
use App\Pdf\PdfResponse;
use App\Report\CustomersReport;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for customer entities.
 *
 * @see \App\Entity\Customer
 *
 * @Route("/customer")
 * @IsGranted("ROLE_USER")
 */
class CustomerController extends EntityController
{
    /**
     * The delete route.
     */
    private const ROUTE_DELETE = 'customer_delete';

    /**
     * The list route.
     */
    private const ROUTE_LIST = 'customer_list';

    /**
     * The edit template.
     */
    private const TEMPLATE_EDIT = 'customer/customer_edit.html.twig';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Customer::class);
    }

    /**
     * Add a customer.
     *
     * @Route("/add", name="customer_add", methods={"GET", "POST"})
     */
    public function add(Request $request): Response
    {
        $item = new Customer();

        return $this->editItem($request, ['item' => $item]);
    }

    /**
     * List the customers.
     *
     * @Route("/card", name="customer_card", methods={"GET"})
     */
    public function card(Request $request): Response
    {
        $sortedFields = [
            ['name' => 'nameAndCompany', 'label' => 'customer.fields.nameAndCompany'],
            ['name' => 'zipCity', 'label' => 'customer.fields.zipCity'],
        ];

        return $this->renderCard($request, 'customer/customer_card.html.twig', 'nameAndCompany', Criteria::ASC, $sortedFields);
    }

    /**
     * Delete a customer.
     *
     * @Route("/delete/{id}", name="customer_delete", requirements={"id": "\d+" })
     */
    public function delete(Request $request, Customer $item): Response
    {
        $parameters = [
            'item' => $item,
            'page_list' => self::ROUTE_LIST,
            'page_delete' => self::ROUTE_DELETE,
            'title' => 'customer.delete.title',
            'message' => 'customer.delete.message',
            'success' => 'customer.delete.success',
            'failure' => 'customer.delete.failure',
        ];

        return $this->deletItem($request, $parameters);
    }

    /**
     * Edit a customer.
     *
     * @Route("/edit/{id}", name="customer_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function edit(Request $request, Customer $item): Response
    {
        return $this->editItem($request, ['item' => $item]);
    }

    /**
     * Export the customers to a PDF document.
     *
     * @Route("/pdf", name="customer_pdf")
     */
    public function pdf(Request $request): PdfResponse
    {
        // get customers
        $customers = $this->getRepository()->findAll();
        if (empty($customers)) {
            $message = $this->trans('customer.list.empty');

            throw $this->createNotFoundException($message);
        }

        // create and render report
        $grouped = (bool) $request->get('grouped', true);
        $report = new CustomersReport($this);
        $report->setCustomers($customers)
            ->setGrouped($grouped);

        return $this->renderDocument($report);
    }

    /**
     * Show properties of a customer.
     *
     * @Route("/show/{id}", name="customer_show", requirements={"id": "\d+" }, methods={"GET"})
     */
    public function show(Customer $item): Response
    {
        return $this->showItem('customer/customer_show.html.twig', $item);
    }

    /**
     * Render the table view.
     *
     * @Route("", name="customer_list", methods={"GET", "POST"})
     */
    public function table(Request $request, CustomerDataTable $table): Response
    {
        return $this->showTable($request, $table, 'customer/customer_table.html.twig');
    }

    /**
     * {@inheritdoc}
     */
    protected function editItem(Request $request, array $parameters): Response
    {
        /** @var Customer $item */
        $item = $parameters['item'];

        // update parameters
        $parameters['type'] = CustomerType::class;
        $parameters['template'] = self::TEMPLATE_EDIT;
        $parameters['route'] = self::ROUTE_LIST;
        $parameters['success'] = $item->isNew() ? 'customer.add.success' : 'customer.edit.success';

        return parent::editItem($request, $parameters);
    }
}
