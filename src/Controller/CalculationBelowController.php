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
use App\Attribute\ForAdmin;
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Enums\FlashType;
use App\Model\TranslatableFlashMessage;
use App\Report\CalculationsBelowReport;
use App\Repository\CalculationRepository;
use App\Resolver\DataQueryValueResolver;
use App\Spreadsheet\CalculationsBelowDocument;
use App\Table\CalculationBelowTable;
use App\Table\DataQuery;
use App\Traits\TableTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for calculations where margins are below the minimum.
 */
#[ForAdmin]
#[Route(path: '/calculation/below', name: 'calculation_below_')]
class CalculationBelowController extends AbstractController
{
    use TableTrait;

    /**
     * Export the calculations to a Spreadsheet document.
     */
    #[ExcelRoute]
    public function excel(CalculationRepository $repository): Response
    {
        return $this->export(
            $repository,
            fn (iterable $entities): Response => $this->renderSpreadsheetDocument(new CalculationsBelowDocument($this, $entities))
        );
    }

    /**
     * Render the table view.
     */
    #[IndexRoute]
    public function index(
        CalculationBelowTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'calculation/calculation_table_below.html.twig');
    }

    /**
     * Export calculations to a PDF document.
     */
    #[PdfRoute]
    public function pdf(CalculationRepository $repository): Response
    {
        return $this->export(
            $repository,
            fn (iterable $entities): Response => $this->renderPdfDocument(new CalculationsBelowReport($this, $entities))
        );
    }

    /**
     * @param callable(iterable): Response $callback
     */
    private function export(CalculationRepository $repository, callable $callback): Response
    {
        $minMargin = $this->getMinMargin();
        $response = $this->getEmptyResponse($repository, $minMargin);
        if ($response instanceof RedirectResponse) {
            return $response;
        }

        return $callback($repository->getItemsBelow($minMargin));
    }

    /**
     * Returns a response if no calculation is below the given margin.
     */
    private function getEmptyResponse(CalculationRepository $repository, float $minMargin): ?RedirectResponse
    {
        if (0 === $repository->countItemsBelow($minMargin)) {
            return $this->redirectToHomePage(
                message: TranslatableFlashMessage::instance(
                    message: 'below.empty',
                    type: FlashType::WARNING
                )
            );
        }

        return null;
    }
}
