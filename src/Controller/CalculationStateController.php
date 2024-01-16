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

use App\Entity\CalculationState;
use App\Form\CalculationState\CalculationStateType;
use App\Interfaces\EntityInterface;
use App\Interfaces\RoleInterface;
use App\Report\CalculationStatesReport;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\CalculationStatesDocument;
use App\Table\CalculationStateTable;
use App\Table\DataQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for calculation state entities.
 *
 * @template-extends AbstractEntityController<CalculationState, CalculationStateRepository>
 */
#[AsController]
#[Route(path: '/calculationstate')]
#[IsGranted(RoleInterface::ROLE_USER)]
class CalculationStateController extends AbstractEntityController
{
    public function __construct(CalculationStateRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a new calculation state.
     */
    #[Route(path: '/add', name: 'calculationstate_add', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new CalculationState());
    }

    /**
     * Clone (copy) a calculation state.
     */
    #[Route(path: '/clone/{id}', name: 'calculationstate_clone', requirements: ['id' => Requirement::DIGITS], methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function clone(Request $request, CalculationState $item): Response
    {
        $code = $this->trans('common.clone_description', ['%description%' => $item->getCode()]);
        $clone = $item->clone($code);
        $parameters = [
            'title' => 'calculationstate.clone.title',
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Delete a calculation state.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Route(path: '/delete/{id}', name: 'calculationstate_delete', requirements: ['id' => Requirement::DIGITS], methods: [Request::METHOD_GET, Request::METHOD_DELETE])]
    public function delete(Request $request, CalculationState $item, CalculationRepository $repository, LoggerInterface $logger): Response
    {
        $count = $repository->countStateReferences($item);
        if (0 !== $count) {
            $display = $item->getDisplay();
            $calculations = $this->trans('counters.calculations_lower', ['count' => $count]);
            $message = $this->trans('calculationstate.delete.failure', [
                '%name%' => $display,
                '%calculations%' => $calculations,
                ]);
            $parameters = [
                'title' => 'calculationstate.delete.title',
                'message' => $message,
                'item' => $item,
                'back_page' => $this->getDefaultRoute(),
                'back_text' => 'common.button_back_list',
            ];

            return $this->render('cards/card_warning.html.twig', $parameters);
        }

        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Edit a calculation state.
     */
    #[Route(path: '/edit/{id}', name: 'calculationstate_edit', requirements: ['id' => Requirement::DIGITS], methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function edit(Request $request, CalculationState $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the calculation states to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no calculation state is found
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Route(path: '/excel', name: 'calculationstate_excel', methods: Request::METHOD_GET)]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('code');
        if ([] === $entities) {
            $message = $this->trans('calculationstate.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new CalculationStatesDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export the calculation states to a PDF document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no calculation state is found
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Route(path: '/pdf', name: 'calculationstate_pdf', methods: Request::METHOD_GET)]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities('code');
        if ([] === $entities) {
            $message = $this->trans('calculationstate.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new CalculationStatesReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a calculation state.
     */
    #[Route(path: '/show/{id}', name: 'calculationstate_show', requirements: ['id' => Requirement::DIGITS], methods: Request::METHOD_GET)]
    public function show(CalculationState $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     */
    #[Route(path: '', name: 'calculationstate_table', methods: Request::METHOD_GET)]
    public function table(
        CalculationStateTable $table,
        LoggerInterface $logger,
        #[MapQueryString]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'calculationstate/calculationstate_table.html.twig');
    }

    /**
     * @psalm-param CalculationState $item
     */
    protected function deleteFromDatabase(EntityInterface $item): void
    {
        $this->getApplication()->updateDeletedState($item);
        parent::deleteFromDatabase($item);
    }

    protected function getEditFormType(): string
    {
        return CalculationStateType::class;
    }
}
