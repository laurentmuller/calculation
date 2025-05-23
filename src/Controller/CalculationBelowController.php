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
use App\Entity\Calculation;
use App\Enums\FlashType;
use App\Interfaces\RoleInterface;
use App\Report\CalculationsBelowReport;
use App\Repository\CalculationRepository;
use App\Resolver\DataQueryValueResolver;
use App\Spreadsheet\CalculationsDocument;
use App\Table\CalculationBelowTable;
use App\Table\DataQuery;
use App\Traits\TableTrait;
use App\Utils\FormatUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for calculations where margins are below the minimum.
 */
#[AsController]
#[Route(path: '/calculation/below', name: 'calculation_below_')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class CalculationBelowController extends AbstractController
{
    use TableTrait;

    /**
     * Export the calculations to a Spreadsheet document.
     */
    #[Get(path: self::EXCEL_PATH, name: self::EXCEL_NAME)]
    public function excel(CalculationRepository $repository): Response
    {
        $minMargin = $this->getMinMargin();
        $response = $this->getEmptyResponse($repository, $minMargin);
        if ($response instanceof RedirectResponse) {
            return $response;
        }
        $items = $this->getItems($repository, $minMargin);
        $doc = new CalculationsDocument($this, $items);
        $doc->setTitleTrans('below.title');
        $doc->setDescriptionTrans(
            'below.description',
            ['%margin%' => FormatUtils::formatPercent($minMargin)]
        );

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[Get(path: self::INDEX_PATH, name: self::INDEX_NAME)]
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
    #[Get(path: self::PDF_PATH, name: self::PDF_NAME)]
    public function pdf(CalculationRepository $repository): Response
    {
        $minMargin = $this->getMinMargin();
        $response = $this->getEmptyResponse($repository, $minMargin);
        if ($response instanceof RedirectResponse) {
            return $response;
        }
        $items = $this->getItems($repository, $minMargin);
        $doc = new CalculationsBelowReport($this, $items);
        $doc->setTitleTrans('below.title');
        $doc->setDescriptionTrans(
            'below.description',
            ['%margin%' => FormatUtils::formatPercent($minMargin)]
        );

        return $this->renderPdfDocument($doc);
    }

    /**
     * Returns a response if no calculation is below the given margin.
     */
    private function getEmptyResponse(CalculationRepository $repository, float $minMargin): ?RedirectResponse
    {
        if (0 === $repository->countItemsBelow($minMargin)) {
            return $this->redirectToHomePage(id: 'below.empty', type: FlashType::WARNING);
        }

        return null;
    }

    /**
     * Gets items to display.
     *
     * @return Calculation[]
     */
    private function getItems(CalculationRepository $repository, float $minMargin): array
    {
        return $repository->getItemsBelow($minMargin);
    }
}
