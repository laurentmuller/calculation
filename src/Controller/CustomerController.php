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

use App\DataTable\CustomerDataTable;
use App\Entity\AbstractEntity;
use App\Entity\Customer;
use App\Form\Customer\CustomerType;
use App\Pdf\PdfResponse;
use App\Report\CustomersReport;
use App\Repository\CustomerRepository;
use App\Service\SpreadsheetService;
use Doctrine\Common\Collections\Criteria;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for customer entities.
 *
 * @see \App\Entity\Customer
 *
 * @Route("/customer")
 * @IsGranted("ROLE_USER")
 */
class CustomerController extends AbstractEntityController
{
    /**
     * The list route.
     */
    private const ROUTE_LIST = 'customer_list';

    /**
     * The table route.
     */
    private const ROUTE_TABLE = 'customer_table';

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
        return $this->editEntity($request, new Customer());
    }

    /**
     * List the customers.
     *
     * @Route("", name="customer_list", methods={"GET"})
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
     * @Route("/delete/{id}", name="customer_delete", requirements={"id": "\d+" })
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
     * @Route("/edit/{id}", name="customer_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function edit(Request $request, Customer $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the customers to an Excel document.
     *
     * @Route("/excel", name="customer_excel")
     */
    public function excel(CustomerRepository $repository, SpreadsheetService $service, TranslatorInterface $translator): Response
    {
        /** @var Customer[] $customers */
        $customers = $repository->findAllByNameAndCompany();

        $title = $translator->trans('customer.list.title');

        // properties
        $properties = [
            SpreadsheetService::P_TITLE => $title,
            SpreadsheetService::P_ACTIVE_TITLE => $title,
            SpreadsheetService::P_USER_NAME => $this->getUserName(),
            SpreadsheetService::P_APPLICATION => $this->getApplicationName(),
            SpreadsheetService::P_COMPANY => $this->getApplication()->getCustomerName(),
            SpreadsheetService::P_GRIDLINE => true,
        ];
        $service->initialize($properties);

        // headers
        $service->setHeaderValues([
            'customer.fields.lastName' => Alignment::HORIZONTAL_GENERAL,
            'customer.fields.firstName' => Alignment::HORIZONTAL_GENERAL,
            'customer.fields.company' => Alignment::HORIZONTAL_GENERAL,
            'customer.fields.address' => Alignment::HORIZONTAL_GENERAL,
            'customer.fields.zipCode' => Alignment::HORIZONTAL_RIGHT,
            'customer.fields.city' => Alignment::HORIZONTAL_GENERAL,
        ]);

        // customers
        $row = 2;
        foreach ($customers as $customer) {
            $service->setRowValues($row++, [
                $customer->getLastName(),
                $customer->getFirstName(),
                $customer->getCompany(),
                $customer->getAddress(),
                $customer->getZipCode(),
                $customer->getCity(),
            ]);
        }
        $service->setSelectedCell('A2');

        return $service->xlsxResponse();
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
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("/table", name="customer_table", methods={"GET", "POST"})
     */
    public function table(Request $request, CustomerDataTable $table): Response
    {
        return $this->renderTable($request, $table);
    }

    /**
     * {@inheritdoc}
     *
     * @param Customer $item
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
    protected function getCardTemplate(): string
    {
        return 'customer/customer_card.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultRoute(): string
    {
        return $this->isDisplayTabular() ? self::ROUTE_TABLE : self::ROUTE_LIST;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditFormType(): string
    {
        return CustomerType::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditTemplate(): string
    {
        return 'customer/customer_edit.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getShowTemplate(): string
    {
        return 'customer/customer_show.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTableTemplate(): string
    {
        return 'customer/customer_table.html.twig';
    }
}
