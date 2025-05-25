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

use App\Attribute\ExcelRoute;
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Enums\FlashType;
use App\Interfaces\RoleInterface;
use App\Report\CalculationsDuplicateReport;
use App\Repository\CalculationRepository;
use App\Resolver\DataQueryValueResolver;
use App\Spreadsheet\CalculationsDuplicateDocument;
use App\Table\CalculationDuplicateTable;
use App\Table\DataQuery;
use App\Traits\TableTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display and export duplicate items in the calculations.
 *
 * @phpstan-import-type CalculationItemType from CalculationRepository
 */
#[AsController]
#[Route(path: '/calculation/duplicate', name: 'calculation_duplicate_')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class CalculationDuplicateController extends AbstractController
{
    use TableTrait;

    /**
     * Export the duplicate items to a Spreadsheet document.
     */
    #[ExcelRoute]
    public function excel(CalculationRepository $repository): Response
    {
        $response = $this->getEmptyResponse($repository);
        if ($response instanceof RedirectResponse) {
            return $response;
        }
        $items = $this->getItems($repository);
        $doc = new CalculationsDuplicateDocument($this, $items);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[IndexRoute]
    public function index(
        CalculationDuplicateTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'calculation/calculation_table_duplicate.html.twig');
    }

    /**
     * Exports the duplicate items in the calculations.
     */
    #[PdfRoute]
    public function pdf(CalculationRepository $repository): Response
    {
        $response = $this->getEmptyResponse($repository);
        if ($response instanceof RedirectResponse) {
            return $response;
        }
        $items = $this->getItems($repository);
        $doc = new CalculationsDuplicateReport($this, $items);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Returns a response if no item is duplicated.
     */
    private function getEmptyResponse(CalculationRepository $repository): ?RedirectResponse
    {
        if (0 === $repository->countItemsDuplicate()) {
            return $this->redirectToHomePage(id: 'duplicate.empty', type: FlashType::WARNING);
        }

        return null;
    }

    /**
     * Gets items to display.
     *
     * @phpstan-return CalculationItemType[]
     */
    private function getItems(CalculationRepository $repository): array
    {
        return $repository->getItemsDuplicate();
    }
}
