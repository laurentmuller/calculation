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

use AndreaSprega\Bundle\BreadcrumbBundle\Annotation\Breadcrumb;
use App\DataTable\CalculationStateDataTable;
use App\Entity\AbstractEntity;
use App\Entity\CalculationState;
use App\Excel\ExcelResponse;
use App\Form\CalculationState\CalculationStateType;
use App\Interfaces\ApplicationServiceInterface;
use App\Pdf\PdfResponse;
use App\Report\CalculationStatesReport;
use App\Repository\CalculationRepository;
use App\Spreadsheet\CalculationStateDocument;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for calculation state entities.
 *
 * @author Laurent Muller
 *
 * @Route("/calculationstate")
 * @IsGranted("ROLE_USER")
 * @Breadcrumb({
 *     {"label" = "index.title", "route" = "homepage" },
 *     {"label" = "calculationstate.list.title", "route" = "table_calculationstate" }
 * })
 */
class CalculationStateController extends AbstractEntityController
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(CalculationState::class);
    }

    /**
     * Add a new calculation state.
     *
     * @Route("/add", name="calculationstate_add")
     * @Breadcrumb({
     *     {"label" = "breadcrumb.add"}
     * })
     */
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new CalculationState());
    }

    /**
     * Render the card view.
     *
     * @Route("/card", name="calculationstate_card")
     */
    public function card(Request $request): Response
    {
        return $this->renderCard($request, 'code');
    }

    /**
     * Clone (copy) a calculation state.
     *
     * @Route("/clone/{id}", name="calculationstate_clone", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "breadcrumb.clone" }
     * })
     */
    public function clone(Request $request, CalculationState $item): Response
    {
        $code = $this->trans('common.clone_description', ['%description%' => $item->getCode()]);
        $clone = $item->clone($code);

        return $this->editEntity($request, $clone);
    }

    /**
     * Delete a calculation state.
     *
     * @Route("/delete/{id}", name="calculationstate_delete", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.delete" }
     * })
     */
    public function delete(Request $request, CalculationState $item, CalculationRepository $repository): Response
    {
        // calculation?
        $calculations = $repository->countStateReferences($item);
        if (0 !== $calculations) {
            $display = $item->getDisplay();
            $calculationsText = $this->trans('counters.calculations_lower', ['count' => $calculations]);
            $message = $this->trans('calculationstate.delete.failure', [
                '%name%' => $display,
                '%calculations%' => $calculationsText,
                ]);
            $parameters = [
                'item' => $item,
                'id' => $item->getId(),
                'title' => 'calculationstate.delete.title',
                'message' => $message,
                'back_page' => $this->getDefaultRoute(),
                'back_text' => 'common.button_back_list',
            ];

            return $this->render('cards/card_warning.html.twig', $parameters);
        }

        $parameters = [
            'title' => 'calculationstate.delete.title',
            'message' => 'calculationstate.delete.message',
            'success' => 'calculationstate.delete.success',
            'failure' => 'calculationstate.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $parameters);
    }

    /**
     * Edit a calculation state.
     *
     * @Route("/edit/{id}", name="calculationstate_edit", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.edit" }
     * })
     */
    public function edit(Request $request, CalculationState $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the calculation states to an Excel document.
     *
     * @Route("/excel", name="calculationstate_excel")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no calculation state is found
     */
    public function excel(): ExcelResponse
    {
        /** @var CalculationState[] $entities */
        $entities = $this->getEntities('code');
        if (empty($entities)) {
            $message = $this->trans('calculationstate.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new CalculationStateDocument($this, $entities);

        return $this->renderExcelDocument($doc);
    }

    /**
     * Export the calculation states to a PDF document.
     *
     * @Route("/pdf", name="calculationstate_pdf")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no calculation state is found
     */
    public function pdf(): PdfResponse
    {
        /** @var CalculationState[] $entities */
        $entities = $this->getEntities('code');
        if (empty($entities)) {
            $message = $this->trans('calculationstate.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new CalculationStatesReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a calculation state.
     *
     * @Route("/show/{id}", name="calculationstate_show", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.property" }
     * })
     */
    public function show(CalculationState $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("", name="calculationstate_table")
     */
    public function table(Request $request, CalculationStateDataTable $table): Response
    {
        // callback?
        $attributes = [];
        if (!$request->isXmlHttpRequest()) {
            $route = $this->isDisplayTabular() ? 'calculation_table' : 'calculationstate_list';
            $attributes = [
                'link_href' => $this->generateUrl($route),
                'link_title' => $this->trans('calculationstate.list.calculation_title'),
            ];
        }

        return $this->renderTable($request, $table, $attributes);
    }

    /**
     * {@inheritdoc}
     *
     * @param CalculationState $item
     */
    protected function deleteFromDatabase(AbstractEntity $item): void
    {
        // update default state (if applicable)
        $id = $this->getApplication()->getDefaultStateId();
        if ($id === $item->getId()) {
            $this->getApplication()->setProperties([ApplicationServiceInterface::P_DEFAULT_STATE => null]);
        }
        parent::deleteFromDatabase($item);
    }

    /**
     * {@inheritdoc}
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        // update parameters
        $parameters['success'] = $item->isNew() ? 'calculationstate.add.success' : 'calculationstate.edit.success';

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditFormType(): string
    {
        return CalculationStateType::class;
    }
}
