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
use App\Entity\Product;
use App\Form\Product\ProductType;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Report\ProductsReport;
use App\Repository\ProductRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\ProductsDocument;
use App\Table\ProductTable;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for product entities.
 *
 * @template-extends AbstractEntityController<Product>
 */
#[AsController]
#[Route(path: '/product')]
#[IsGranted(RoleInterface::ROLE_USER)]
class ProductController extends AbstractEntityController
{
    /**
     * Constructor.
     *
     * @throws \ReflectionException
     */
    public function __construct(ProductRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a product.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/add', name: 'product_add')]
    public function add(Request $request): Response
    {
        $item = new Product();
        if (null !== ($category = $this->getApplication()->getDefaultCategory())) {
            $item->setCategory($category);
        }

        return $this->editEntity($request, $item);
    }

    /**
     * Clone (copy) a product.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/clone/{id}', name: 'product_clone', requirements: ['id' => self::DIGITS])]
    public function clone(Request $request, Product $item): Response
    {
        $description = $this->trans('common.clone_description', ['%description%' => $item->getDescription()]);
        $clone = $item->clone($description);
        $parameters = [
            'params' => ['id' => $item->getId()],
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Delete a product.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \ReflectionException
     */
    #[Route(path: '/delete/{id}', name: 'product_delete', requirements: ['id' => self::DIGITS])]
    public function delete(Request $request, Product $item, LoggerInterface $logger): Response
    {
        $parameters = [
            'title' => 'product.delete.title',
            'message' => 'product.delete.message',
            'failure' => 'product.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $logger, $parameters);
    }

    /**
     * Edit a product.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/edit/{id}', name: 'product_edit', requirements: ['id' => self::DIGITS])]
    public function edit(Request $request, Product $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the products to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no product is found
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Route(path: '/excel', name: 'product_excel')]
    public function excel(ProductRepository $repository): SpreadsheetResponse
    {
        $entities = $repository->findAllByGroup();
        if (empty($entities)) {
            $message = $this->trans('product.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new ProductsDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export the products to a PDF document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no product is found
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/pdf', name: 'product_pdf')]
    public function pdf(ProductRepository $repository): PdfResponse
    {
        $entities = $repository->findAllByGroup();
        if (empty($entities)) {
            $message = $this->trans('product.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new ProductsReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a product.
     */
    #[Route(path: '/show/{id}', name: 'product_show', requirements: ['id' => self::DIGITS])]
    public function show(Product $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @throws \ReflectionException
     */
    #[Route(path: '', name: 'product_table')]
    public function table(Request $request, ProductTable $table, LoggerInterface $logger): Response
    {
        return $this->handleTableRequest($request, $table, 'product/product_table.html.twig', $logger);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function deleteFromDatabase(AbstractEntity $item): void
    {
        // update default product (if applicable)
        $application = $this->getApplication();
        $id = $application->getDefaultProductId();
        if ($id === $item->getId()) {
            $application->setProperty(PropertyServiceInterface::P_DEFAULT_PRODUCT, null);
        }

        parent::deleteFromDatabase($item);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditFormType(): string
    {
        return ProductType::class;
    }
}
