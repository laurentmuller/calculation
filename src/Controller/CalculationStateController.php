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

use App\Attribute\Get;
use App\Attribute\GetDelete;
use App\Attribute\GetPost;
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
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for calculation state entities.
 *
 * @template-extends AbstractEntityController<CalculationState, CalculationStateRepository>
 */
#[AsController]
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
    #[GetPost(path: self::CLONE_PATH, name: self::CLONE_NAME, requirements: self::ID_REQUIREMENT)]
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
    #[GetDelete(path: self::DELETE_PATH, name: self::DELETE_NAME, requirements: self::ID_REQUIREMENT)]
    public function delete(
        Request $request,
        CalculationState $item,
        CalculationRepository $repository,
        LoggerInterface $logger
    ): Response {
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
     * Add or edit a calculation state.
     */
    #[GetPost(path: self::ADD_PATH, name: self::ADD_NAME)]
    #[GetPost(path: self::EDIT_PATH, name: self::EDIT_NAME, requirements: self::ID_REQUIREMENT)]
    public function edit(Request $request, ?CalculationState $item): Response
    {
        return $this->editEntity($request, $item ?? new CalculationState());
    }

    /**
     * Export the calculation states to a Spreadsheet document.
     */
    #[Get(path: self::EXCEL_PATH, name: self::EXCEL_NAME)]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('code');
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('calculationstate.list.empty');
        }
        $doc = new CalculationStatesDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[Get(path: self::INDEX_PATH, name: self::INDEX_NAME)]
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
    #[Get(path: self::PDF_PATH, name: self::PDF_NAME)]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities('code');
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('calculationstate.list.empty');
        }
        $doc = new CalculationStatesReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a calculation state.
     */
    #[Get(path: self::SHOW_PATH, name: self::SHOW_NAME, requirements: self::ID_REQUIREMENT)]
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
}
