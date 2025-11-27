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

use App\Attribute\AddEntityRoute;
use App\Attribute\CloneEntityRoute;
use App\Attribute\DeleteEntityRoute;
use App\Attribute\EditEntityRoute;
use App\Attribute\ExcelRoute;
use App\Attribute\ForUser;
use App\Attribute\GetPostRoute;
use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Attribute\ShowEntityRoute;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Product;
use App\Form\Calculation\CalculationEditStateType;
use App\Interfaces\EntityInterface;
use App\Interfaces\SortModeInterface;
use App\Report\CalculationReport;
use App\Report\CalculationsReport;
use App\Repository\CalculationRepository;
use App\Resolver\DataQueryValueResolver;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\CalculationGroupService;
use App\Service\CalculationUpdateService;
use App\Spreadsheet\CalculationDocument;
use App\Spreadsheet\CalculationsDocument;
use App\Table\CalculationTable;
use App\Table\DataQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Controller for calculation entities.
 *
 * @extends AbstractEntityController<Calculation, CalculationRepository>
 */
#[ForUser]
#[Route(path: '/calculation', name: 'calculation_')]
class CalculationController extends AbstractEntityController
{
    public function __construct(
        CalculationRepository $repository,
        private readonly CalculationGroupService $groupService,
        private readonly CalculationUpdateService $updateService,
    ) {
        parent::__construct($repository);
    }

    /**
     * Edit a copy (cloned) calculation.
     */
    #[CloneEntityRoute]
    public function clone(Request $request, Calculation $item): Response
    {
        $description = $this->trans('common.clone_description', ['%description%' => $item->getDescription()]);
        $state = $this->getApplicationParameters()->getDefaultState();
        $clone = $item->clone($state, $description);
        $parameters = [
            'title' => 'calculation.clone.title',
            'params' => ['id' => $item->getId()],
            'overall_below' => $this->isMarginBelow($clone),
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Delete a calculation.
     */
    #[DeleteEntityRoute]
    public function delete(Request $request, Calculation $item, LoggerInterface $logger): Response
    {
        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Add or edit a calculation.
     */
    #[AddEntityRoute]
    #[EditEntityRoute]
    public function edit(Request $request, ?Calculation $item): Response
    {
        return $this->editEntity($request, $item ?? $this->createCalculation());
    }

    /**
     * Export the calculations to a Spreadsheet document.
     */
    #[ExcelRoute]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities(['id' => SortModeInterface::SORT_DESC]);
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('calculation.list.empty');
        }

        return $this->renderSpreadsheetDocument(new CalculationsDocument($this, $entities));
    }

    /**
     * Export a single calculation to a Spreadsheet document.
     */
    #[GetRoute(path: '/excel/{id}', name: 'excel_id', requirements: self::ID_REQUIREMENT)]
    public function excelOne(Calculation $calculation): SpreadsheetResponse
    {
        return $this->renderSpreadsheetDocument(new CalculationDocument($this, $calculation));
    }

    /**
     * Render the table view.
     */
    #[IndexRoute]
    public function index(
        CalculationTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'calculation/calculation_table.html.twig');
    }

    /**
     * Export calculations to a PDF document.
     */
    #[PdfRoute]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities([
            'editable' => SortModeInterface::SORT_DESC,
            'code' => SortModeInterface::SORT_ASC,
            'id' => SortModeInterface::SORT_DESC,
        ]);
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('calculation.list.empty');
        }

        return $this->renderPdfDocument(new CalculationsReport($this, $entities));
    }

    /**
     * Export a single calculation to a PDF document.
     */
    #[GetRoute(path: '/pdf/{id}', name: 'pdf_id', requirements: self::ID_REQUIREMENT)]
    public function pdfOne(Calculation $calculation): PdfResponse
    {
        $minMargin = $this->getMinMargin();
        $qrcode = $this->getQrCode($calculation);

        return $this->renderPdfDocument(new CalculationReport($this, $calculation, $minMargin, $qrcode));
    }

    /**
     * Show properties of a calculation.
     */
    #[ShowEntityRoute]
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
    #[GetPostRoute(path: '/state/{id}', name: 'state', requirements: self::ID_REQUIREMENT)]
    public function state(Request $request, Calculation $item): Response
    {
        $form = $this->createForm(CalculationEditStateType::class, $item);
        if ($this->handleRequestForm($request, $form)) {
            $this->getRepository()->flush();

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
     * @param Calculation $item
     */
    #[\Override]
    protected function editEntity(Request $request, EntityInterface $item, array $parameters = []): Response
    {
        $parameters['min_margin'] = $this->getMinMargin();
        $parameters['empty_items'] = $item->hasEmptyItems();
        $parameters['duplicate_items'] = $item->hasDuplicateItems();
        $parameters['overall_below'] = $this->isMarginBelow($item);
        $parameters['groups'] = $this->groupService->createGroups($item);
        $parameters['editable'] = $item->isEditable();
        if ($item->isEditable()) {
            $parameters['group_index'] = $item->getGroupsCount();
            $parameters['category_index'] = $item->getCategoriesCount();
            $parameters['item_index'] = $item->getLinesCount();
        }

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * @phpstan-param Calculation $item
     */
    #[\Override]
    protected function saveToDatabase(EntityInterface $item): void
    {
        $this->updateService->updateCalculation($item);
        parent::saveToDatabase($item);
    }

    private function createCalculation(): Calculation
    {
        $calculation = new Calculation();
        $application = $this->getApplicationParameters();
        $state = $application->getDefaultState();
        if ($state instanceof CalculationState) {
            $calculation->setState($state);
        }
        $product = $application->getDefaultProduct();
        if ($product instanceof Product) {
            $calculation->addProduct($product, $application->getProduct()->getQuantity());
            $this->updateService->updateCalculation($calculation);
        }

        return $calculation;
    }

    /**
     * Gets the QR-code for the given calculation.
     */
    private function getQrCode(Calculation $calculation): string
    {
        if (!$this->getUserParameters()->getOptions()->isQrCode()) {
            return '';
        }

        return $this->generateUrl(
            'calculation_show',
            ['id' => $calculation->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function isMarginBelow(Calculation $calculation): bool
    {
        return $calculation->isMarginBelow($this->getMinMargin());
    }
}
