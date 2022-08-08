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

use App\Entity\AbstractEntity;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for customer entities.
 *
 * @see \App\Entity\Customer
 *
 * @template-extends AbstractEntityController<Customer>
 */
#[AsController]
#[Route(path: '/customer')]
#[IsGranted(RoleInterface::ROLE_USER)]
class CustomerController extends AbstractEntityController
{
    /**
     * Constructor.
     *
     * @throws \ReflectionException
     */
    public function __construct(CustomerRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a customer.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/add', name: 'customer_add')]
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new Customer());
    }

    /**
     * Delete a customer.
     *
     * throws \Psr\Container\ContainerExceptionInterface
     *
     * @throws \ReflectionException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/delete/{id}', name: 'customer_delete', requirements: ['id' => self::DIGITS])]
    public function delete(Request $request, Customer $item, LoggerInterface $logger): Response
    {
        $parameters = [
            'title' => 'customer.delete.title',
            'message' => 'customer.delete.message',
            'success' => 'customer.delete.success',
            'failure' => 'customer.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $logger, $parameters);
    }

    /**
     * Edit a customer.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/edit/{id}', name: 'customer_edit', requirements: ['id' => self::DIGITS])]
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
    #[Route(path: '/excel', name: 'customer_excel')]
    public function excel(CustomerRepository $repository): SpreadsheetResponse
    {
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
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no customer is found
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/pdf', name: 'customer_pdf')]
    public function pdf(Request $request, CustomerRepository $repository): PdfResponse
    {
        $entities = $repository->findAllByNameAndCompany();
        if (empty($entities)) {
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
    #[Route(path: '/show/{id}', name: 'customer_show', requirements: ['id' => self::DIGITS])]
    public function show(Customer $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @throws \ReflectionException
     */
    #[Route(path: '', name: 'customer_table')]
    public function table(Request $request, CustomerTable $table, LoggerInterface $logger): Response
    {
        return $this->handleTableRequest($request, $table, 'customer/customer_table.html.twig', $logger);
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
