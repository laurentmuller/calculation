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

use App\DataTable\CalculationDataTable;
use App\Entity\AbstractEntity;
use App\Entity\Calculation;
use App\Entity\Task;
use App\Excel\ExcelResponse;
use App\Form\Calculation\CalculationEditStateType;
use App\Form\Calculation\CalculationType;
use App\Form\Dialog\EditItemDialogType;
use App\Form\Dialog\EditTaskDialogType;
use App\Pdf\PdfResponse;
use App\Report\CalculationReport;
use App\Report\CalculationsReport;
use App\Repository\CalculationStateRepository;
use App\Service\CalculationService;
use App\Spreadsheet\CalculationDocument;
use App\Util\FormatUtils;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for calculation entities.
 *
 * @author Laurent Muller
 *
 * @Route("/calculation")
 * @IsGranted("ROLE_USER")
 * @Breadcrumb({
 *     {"label" = "index.title", "route" = "homepage" },
 *     {"label" = "calculation.list.title", "route" = "table_calculation", "params" = {
 *         "id" = "$params.[id]",
 *         "search" = "$params.[search]",
 *         "sort" = "$params.[sort]",
 *         "order" = "$params.[order]",
 *         "offset" = "$params.[offset]",
 *         "limit" = "$params.[limit]",
 *         "view" = "$params.[view]"
 *     }}
 * })
 * @template-extends AbstractEntityController<Calculation>
 */
class CalculationController extends AbstractEntityController
{
    /**
     * The service to compute calculations.
     */
    private CalculationService $service;

    /**
     * Constructor.
     *
     * @param CalculationService $service the service to compute calculations
     */
    public function __construct(CalculationService $service)
    {
        parent::__construct(Calculation::class);
        $this->service = $service;
    }

    /**
     * Add a new calculation.
     *
     * @Route("/add", name="calculation_add")
     * @Breadcrumb({
     *     {"label" = "breadcrumb.add"}
     * })
     */
    public function add(Request $request): Response
    {
        // create
        $item = new Calculation();
        if ($state = $this->getApplication()->getDefaultState()) {
            $item->setState($state);
        }

        $parameters = ['overall_below' => false];

        return $this->editEntity($request, $item, $parameters);
    }

    /**
     * Show the calculations, as card.
     *
     * @Route("/card", name="calculation_card")
     */
    public function card(Request $request): Response
    {
        $sortedFields = [
            ['name' => 'id', 'label' => 'calculation.fields.id', 'numeric' => true],
            ['name' => 'date', 'label' => 'calculation.fields.date'],
            ['name' => 'customer', 'label' => 'calculation.fields.customer'],
            ['name' => 'description', 'label' => 'calculation.fields.description'],
            ['name' => 'overallTotal', 'label' => 'calculation.fields.total', 'numeric' => true],
        ];
        $parameters = [
            'min_margin' => $this->getApplication()->getMinMargin(),
        ];

        return $this->renderCard($request, 'id', Criteria::DESC, $sortedFields, $parameters);
    }

    /**
     * Edit a copy (cloned) calculation.
     *
     * @Route("/clone/{id}", name="calculation_clone", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "breadcrumb.clone"}
     * })
     */
    public function clone(Request $request, Calculation $item): Response
    {
        // clone
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
     *
     * @Route("/delete/{id}", name="calculation_delete", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.delete"}
     * })
     */
    public function delete(Request $request, Calculation $item): Response
    {
        // parameters
        $parameters = [
            'title' => 'calculation.delete.title',
            'message' => 'calculation.delete.message',
            'success' => 'calculation.delete.success',
            'failure' => 'calculation.delete.failure',
        ];

        // delete
        return $this->deleteEntity($request, $item, $parameters);
    }

    /**
     * Edit a calculation.
     *
     * @Route("/edit/{id}", name="calculation_edit", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.edit" }
     * })
     */
    public function edit(Request $request, Calculation $item): Response
    {
        $parameters = ['overall_below' => $this->isMarginBelow($item)];

        return $this->editEntity($request, $item, $parameters);
    }

    /**
     * Export the calculations to an Excel document.
     *
     * @Route("/excel", name="calculation_excel")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no calculation is found
     */
    public function excel(): ExcelResponse
    {
        /** @var Calculation[] $entities */
        $entities = $this->getEntities('id');
        if (empty($entities)) {
            $message = $this->trans('calculation.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new CalculationDocument($this, $entities);

        return $this->renderExcelDocument($doc);
    }

    /**
     * Export calculations to a PDF document.
     *
     * @Route("/pdf", name="calculation_pdf")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no calculation is found
     */
    public function pdf(Request $request): PdfResponse
    {
        /** @var Calculation[] $entities */
        $entities = $this->getEntities('id');
        if (empty($entities)) {
            $message = $this->trans('calculation.list.empty');
            throw $this->createNotFoundException($message);
        }

        $grouped = (bool) $request->get('grouped', true);
        $doc = new CalculationsReport($this, $entities, $grouped);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Export a single calculation to a PDF document.
     *
     * @Route("/pdf/{id}", name="calculation_pdf_id", requirements={"id" = "\d+" })
     */
    public function pdfById(Calculation $calculation): PdfResponse
    {
        $doc = new CalculationReport($this, $calculation);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a calculation.
     *
     * @Route("/show/{id}", name="calculation_show", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.property" }
     * })
     */
    public function show(Calculation $item): Response
    {
        $parameters = [
            'min_margin' => $this->getApplication()->getMinMargin(),
            'duplicate_items' => $item->hasDuplicateItems(),
            'emty_items' => $item->hasEmptyItems(),
        ];

        return $this->showEntity($item, $parameters);
    }

    /**
     * Edit the state of a calculation.
     *
     * @Route("/state/{id}", name="calculation_state", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.edit" }
     * })
     */
    public function state(Request $request, Calculation $item): Response
    {
        $oldState = $item->getState();
        $form = $this->createForm(CalculationEditStateType::class, $item);
        if ($this->handleRequestForm($request, $form)) {
            //change?
            if ($oldState !== $item->getState()) {
                // update
                $this->getManager()->flush();
            }

            // message
            $this->succesTrans('calculation.state.success', ['%name%' => $item->getDisplay()]);

            return $this->getUrlGenerator()->redirect($request, $item->getId(), $this->getDefaultRoute());
        }

        // parameters
        $parameters = [
            'form' => $form->createView(),
            'item' => $item,
        ];
        $this->updateQueryParameters($request, $parameters, (int) $item->getId());

        // display
        return $this->render('calculation/calculation_state.html.twig', $parameters);
    }

    /**
     * Show calculations, as table.
     *
     * @Route("", name="calculation_table")
     */
    public function table(Request $request, CalculationDataTable $table, CalculationStateRepository $repository): Response
    {
        $attributes = [];
        $parameters = [];

        // callback?
        if (!$request->isXmlHttpRequest()) {
            // attributes
            $margin = $this->getApplication()->getMinMargin();
            $margin_text = $this->trans('calculation.list.margin_below', ['%minimum%' => FormatUtils::formatPercent($margin)]);
            $attributes = [
                'min_margin' => $margin,
                'min_margin_text' => $margin_text,
            ];

            // parameters
            $states = $repository->getListCountCalculations();
            $parameters = [
                'states' => $states,
            ];
        }

        return $this->renderTable($request, $table, $attributes, $parameters);
    }

    /**
     * {@inheritdoc}
     *
     * @param Calculation $item
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        /* @var Calculation $item */
        $parameters['success'] = $item->isNew() ? 'calculation.add.success' : 'calculation.edit.success';
        $parameters['created_item'] = $item->getCreatedText($this->getTranslator());
        $parameters['updated_item'] = $item->getUpdatedText($this->getTranslator());
        $parameters['groups'] = $this->service->createGroupsFromCalculation($item);
        $parameters['min_margin'] = $this->getApplication()->getMinMargin();
        $parameters['duplicate_items'] = $item->hasDuplicateItems();
        $parameters['emty_items'] = $item->hasEmptyItems();

        // editable?
        if ($parameters['editable'] = $item->isEditable()) {
            $parameters['group_index'] = $item->getGroupsCount();
            $parameters['category_index'] = $item->getCategoriesCount();
            $parameters['item_index'] = $item->getLinesCount();
            $parameters['tasks'] = $this->getTasks();
            $parameters['item_dialog'] = $this->createForm(EditItemDialogType::class)->createView();
            $parameters['task_dialog'] = $this->createForm(EditTaskDialogType::class)->createView();
        }

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditFormType(): string
    {
        return CalculationType::class;
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param Calculation $item
     */
    protected function saveToDatabase(AbstractEntity $item): void
    {
        $this->service->updateTotal($item);
        parent::saveToDatabase($item);
    }

    /**
     * Gets the tasks.
     *
     * @return Task[]
     */
    private function getTasks(): array
    {
        /** @var \App\Repository\TaskRepository $repository */
        $repository = $this->getManager()->getRepository(Task::class);

        return $repository->getSortedBuilder(false)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns if the given calculation has an overall margin below the minimum.
     *
     * @param Calculation $calculation the calculation to verify
     *
     * @return bool true if margin is below
     */
    private function isMarginBelow(Calculation $calculation): bool
    {
        $margin = $this->getApplication()->getMinMargin();

        return $calculation->isMarginBelow($margin);
    }
}
