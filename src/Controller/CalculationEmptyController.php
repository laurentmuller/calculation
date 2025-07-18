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
use App\Report\CalculationsEmptyReport;
use App\Repository\CalculationRepository;
use App\Resolver\DataQueryValueResolver;
use App\Spreadsheet\CalculationsEmptyDocument;
use App\Table\CalculationEmptyTable;
use App\Table\DataQuery;
use App\Traits\TableTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for calculations where items have the price or the quantity is equal to 0.
 *
 * @phpstan-import-type CalculationItemType from CalculationRepository
 */
#[Route(path: '/calculation/empty', name: 'calculation_empty_')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class CalculationEmptyController extends AbstractController
{
    use TableTrait;

    /**
     * Export the empty items to a Spreadsheet document.
     */
    #[ExcelRoute]
    public function excel(CalculationRepository $repository): Response
    {
        $response = $this->getEmptyResponse($repository);
        if ($response instanceof RedirectResponse) {
            return $response;
        }
        $items = $this->getItems($repository);
        $doc = new CalculationsEmptyDocument($this, $items);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[IndexRoute]
    public function index(
        CalculationEmptyTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'calculation/calculation_table_empty.html.twig');
    }

    /**
     * Export the calculations where items have the price or the quantity is equal to 0.
     */
    #[PdfRoute]
    public function pdf(CalculationRepository $repository): Response
    {
        $response = $this->getEmptyResponse($repository);
        if ($response instanceof RedirectResponse) {
            return $response;
        }
        $items = $this->getItems($repository);
        $doc = new CalculationsEmptyReport($this, $items);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Returns a response if no calculation's item is empty.
     */
    private function getEmptyResponse(CalculationRepository $repository): ?RedirectResponse
    {
        if (0 === $repository->countItemsEmpty()) {
            return $this->redirectToHomePage(id: 'empty.empty', type: FlashType::WARNING);
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
        return $repository->getItemsEmpty();
    }
}
