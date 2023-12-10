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

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Product;
use App\Form\Calculation\CalculationEditStateType;
use App\Form\Calculation\CalculationType;
use App\Interfaces\EntityInterface;
use App\Interfaces\RoleInterface;
use App\Report\CalculationReport;
use App\Report\CalculationsReport;
use App\Repository\CalculationRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\CalculationService;
use App\Spreadsheet\CalculationDocument;
use App\Spreadsheet\CalculationsDocument;
use App\Table\CalculationTable;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for calculation entities.
 *
 * @template-extends AbstractEntityController<Calculation, CalculationRepository>
 */
#[AsController]
#[Route(path: '/calculation')]
#[IsGranted(RoleInterface::ROLE_USER)]
class CalculationController extends AbstractEntityController
{
    public function __construct(CalculationRepository $repository, private readonly CalculationService $service)
    {
        parent::__construct($repository);
    }

    /**
     * Add a new calculation.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Route(path: '/add', name: 'calculation_add', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function add(Request $request): Response
    {
        $item = new Calculation();
        $application = $this->getApplication();
        $state = $application->getDefaultState();
        if ($state instanceof CalculationState) {
            $item->setState($state);
        }
        $product = $application->getDefaultProduct();
        if ($product instanceof Product) {
            $item->addProduct($product, $application->getDefaultQuantity());
            $this->service->updateTotal($item);
        }

        return $this->editEntity($request, $item);
    }

    /**
     * Edit a copy (cloned) calculation.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Route(path: '/clone/{id}', name: 'calculation_clone', requirements: ['id' => Requirement::DIGITS], methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function clone(Request $request, Calculation $item): Response
    {
        $description = $this->trans('common.clone_description', ['%description%' => $item->getDescription()]);
        $state = $this->getApplication()->getDefaultState();
        $clone = $item->clone($state, $description);
        $parameters = [
            'params' => ['id' => $item->getId()],
            'overall_below' => $this->isMarginBelow($clone),
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Delete a calculation.
     */
    #[Route(path: '/delete/{id}', name: 'calculation_delete', requirements: ['id' => Requirement::DIGITS], methods: [Request::METHOD_GET, Request::METHOD_DELETE])]
    public function delete(Request $request, Calculation $item, LoggerInterface $logger): Response
    {
        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Edit a calculation.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Route(path: '/edit/{id}', name: 'calculation_edit', requirements: ['id' => Requirement::DIGITS], methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function edit(Request $request, Calculation $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the calculations to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no calculation is found
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Route(path: '/excel', name: 'calculation_excel', methods: Request::METHOD_GET)]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities(['id' => Criteria::DESC]);
        if ([] === $entities) {
            $message = $this->trans('calculation.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new CalculationsDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export a single calculation to a Spreadsheet document.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Route(path: '/excel/{id}', name: 'calculation_excel_id', requirements: ['id' => Requirement::DIGITS], methods: Request::METHOD_GET)]
    public function excelOne(Calculation $calculation): SpreadsheetResponse
    {
        $doc = new CalculationDocument($this, $calculation);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export calculations to a PDF document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no calculation is found
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Route(path: '/pdf', name: 'calculation_pdf', methods: Request::METHOD_GET)]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities([
            'editable' => Criteria::DESC,
            'code' => Criteria::ASC,
            'id' => Criteria::DESC,
        ]);
        if ([] === $entities) {
            $message = $this->trans('calculation.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new CalculationsReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Export a single calculation to a PDF document.
     */
    #[Route(path: '/pdf/{id}', name: 'calculation_pdf_id', requirements: ['id' => Requirement::DIGITS], methods: Request::METHOD_GET)]
    public function pdfOne(Calculation $calculation, UrlGeneratorInterface $generator, LoggerInterface $logger): PdfResponse
    {
        $minMargin = $this->getMinMargin();
        $qrcode = $this->getQrCode($generator, $calculation);
        $doc = new CalculationReport($this, $calculation, $minMargin, $qrcode, $logger);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a calculation.
     */
    #[Route(path: '/show/{id}', name: 'calculation_show', requirements: ['id' => Requirement::DIGITS], methods: Request::METHOD_GET)]
    public function show(Calculation $item): Response
    {
        $parameters = [
            'min_margin' => $this->getMinMargin(),
            'duplicate_items' => $item->hasDuplicateItems(),
            'empty_items' => $item->hasEmptyItems(),
        ];

        return $this->showEntity($item, $parameters);
    }

    /**
     * Edit the state of a calculation.
     */
    #[Route(path: '/state/{id}', name: 'calculation_state', requirements: ['id' => Requirement::DIGITS], methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function state(Request $request, Calculation $item, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(CalculationEditStateType::class, $item);
        if ($this->handleRequestForm($request, $form)) {
            $manager->flush();

            return $this->redirectToDefaultRoute($request, $item);
        }

        $parameters = [
            'form' => $form,
            'item' => $item,
        ];
        $this->updateQueryParameters($request, $parameters, $item);

        return $this->render('calculation/calculation_state.html.twig', $parameters);
    }

    /**
     * Render the table view.
     */
    #[Route(path: '', name: 'calculation_table', methods: Request::METHOD_GET)]
    public function table(Request $request, CalculationTable $table, LoggerInterface $logger): Response
    {
        return $this->handleTableRequest(
            $request,
            $table,
            $logger,
            'calculation/calculation_table.html.twig'
        );
    }

    /**
     * @param Calculation $item
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function editEntity(Request $request, EntityInterface $item, array $parameters = []): Response
    {
        $parameters['min_margin'] = $this->getMinMargin();
        $parameters['empty_items'] = $item->hasEmptyItems();
        $parameters['duplicate_items'] = $item->hasDuplicateItems();
        $parameters['overall_below'] = $this->isMarginBelow($item);
        $parameters['groups'] = $this->service->createGroupsFromCalculation($item);
        $parameters['editable'] = false;
        if ($item->isEditable()) {
            $parameters['editable'] = true;
            $parameters['group_index'] = $item->getGroupsCount();
            $parameters['category_index'] = $item->getCategoriesCount();
            $parameters['item_index'] = $item->getLinesCount();
        }

        return parent::editEntity($request, $item, $parameters);
    }

    protected function getEditFormType(): string
    {
        return CalculationType::class;
    }

    /**
     * @psalm-param Calculation $item
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function saveToDatabase(EntityInterface $item): void
    {
        $this->service->updateTotal($item);
        parent::saveToDatabase($item);
    }

    /**
     * Gets the QR-code for the given calculation.
     */
    private function getQrCode(UrlGeneratorInterface $generator, Calculation $calculation): string
    {
        if ($this->getUserService()->isQrCode()) {
            $name = 'calculation_show';
            $parameters = ['id' => (int) $calculation->getId()];

            return $generator->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return '';
    }

    private function isMarginBelow(Calculation $calculation): bool
    {
        return $this->getApplication()->isMarginBelow($calculation);
    }
}
