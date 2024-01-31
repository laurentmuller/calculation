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
use App\Enums\FlashType;
use App\Interfaces\RoleInterface;
use App\Report\CalculationsEmptyReport;
use App\Repository\CalculationRepository;
use App\Spreadsheet\CalculationsEmptyDocument;
use App\Table\CalculationEmptyTable;
use App\Table\DataQuery;
use App\Traits\TableTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for calculations where items have the price or the quantity is equal to 0.
 *
 * @psalm-import-type CalculationItemType from CalculationRepository
 */
#[AsController]
#[Route(path: '/calculation/empty')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class CalculationEmptyController extends AbstractController
{
    use TableTrait;

    /**
     * Export the empty items to a Spreadsheet document.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Get(path: '/excel', name: 'empty_excel')]
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
     * Export the calculations where items have the price or the quantity is equal to 0.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Get(path: '/pdf', name: 'empty_pdf')]
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
     * Render the table view.
     */
    #[Get(path: '', name: 'empty_table')]
    public function table(
        CalculationEmptyTable $table,
        LoggerInterface $logger,
        #[MapQueryString]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'calculation/calculation_table_empty.html.twig');
    }

    /**
     * Returns a response if no calculation's item is empty.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function getEmptyResponse(CalculationRepository $repository): ?RedirectResponse
    {
        if (0 === $repository->countItemsEmpty()) {
            return $this->redirectToHomePage(message: 'empty.empty', type: FlashType::WARNING);
        }

        return null;
    }

    /**
     * Gets items to display.
     *
     * @psalm-return CalculationItemType[]
     */
    private function getItems(CalculationRepository $repository): array
    {
        return $repository->getItemsEmpty();
    }
}
