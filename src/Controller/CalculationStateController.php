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
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Attribute\ShowEntityRoute;
use App\Entity\CalculationState;
use App\Interfaces\EntityInterface;
use App\Interfaces\RoleInterface;
use App\Report\CalculationStatesReport;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Resolver\DataQueryValueResolver;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\CalculationStatesDocument;
use App\Table\CalculationStateTable;
use App\Table\DataQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for calculation state entities.
 *
 * @template-extends AbstractEntityController<CalculationState, CalculationStateRepository>
 */
#[Route(path: '/calculationstate', name: 'calculationstate_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class CalculationStateController extends AbstractEntityController
{
    public function __construct(CalculationStateRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Clone (copy) a calculation state.
     */
    #[CloneEntityRoute]
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
     */
    #[DeleteEntityRoute]
    public function delete(
        Request $request,
        CalculationState $item,
        CalculationRepository $repository,
        LoggerInterface $logger
    ): Response {
        $count = $repository->countStateReferences($item);
        if (0 !== $count) {
            return $this->showDeleteWarning($item, $count);
        }

        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Add or edit a calculation state.
     */
    #[AddEntityRoute]
    #[EditEntityRoute]
    public function edit(Request $request, ?CalculationState $item): Response
    {
        return $this->editEntity($request, $item ?? new CalculationState());
    }

    /**
     * Export the calculation states to a Spreadsheet document.
     */
    #[ExcelRoute]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('code');
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('calculationstate.list.empty');
        }

        return $this->renderSpreadsheetDocument(new CalculationStatesDocument($this, $entities));
    }

    /**
     * Render the table view.
     */
    #[IndexRoute]
    public function index(
        CalculationStateTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'calculationstate/calculationstate_table.html.twig');
    }

    /**
     * Export the calculation states to a PDF document.
     */
    #[PdfRoute]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities('code');
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('calculationstate.list.empty');
        }

        return $this->renderPdfDocument(new CalculationStatesReport($this, $entities));
    }

    /**
     * Show properties of a calculation state.
     */
    #[ShowEntityRoute]
    public function show(CalculationState $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * @phpstan-param CalculationState $item
     */
    #[\Override]
    protected function deleteFromDatabase(EntityInterface $item): void
    {
        $this->getApplicationService()->updateDeletedState($item);
        parent::deleteFromDatabase($item);
    }

    private function showDeleteWarning(CalculationState $item, int $count): Response
    {
        $message = $this->trans('calculationstate.delete.failure', ['%name%' => $item]);
        $items = [$this->trans('counters.calculations', ['count' => $count])];
        $parameters = [
            'title' => 'calculationstate.delete.title',
            'message' => $message,
            'item' => $item,
            'items' => $items,
            'back_page' => $this->getDefaultRoute(),
            'back_text' => 'common.button_back_list',
        ];

        return $this->render('cards/card_warning.html.twig', $parameters);
    }
}
