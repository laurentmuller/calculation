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

use App\Enums\FlashType;
use App\Interfaces\RoleInterface;
use App\Report\CalculationDuplicateReport;
use App\Repository\CalculationRepository;
use App\Spreadsheet\CalculationsDuplicateDocument;
use App\Table\CalculationDuplicateTable;
use App\Traits\TableTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display and export duplicate items in the calculations.
 */
#[AsController]
#[Route(path: '/duplicate')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class CalculationDuplicateController extends AbstractController
{
    use TableTrait;

    /**
     * Export the duplicate items to a Spreadsheet document.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Route(path: '/excel', name: 'duplicate_excel')]
    public function excel(CalculationRepository $repository): Response
    {
        if (null !== $response = $this->getEmptyResponse($repository)) {
            return $response;
        }
        $items = $this->getItems($repository);
        $doc = new CalculationsDuplicateDocument($this, $items);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Exports the duplicate items in the calculations.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Route(path: '/pdf', name: 'duplicate_pdf')]
    public function pdf(CalculationRepository $repository): Response
    {
        if (null !== $response = $this->getEmptyResponse($repository)) {
            return $response;
        }
        $items = $this->getItems($repository);
        $doc = new CalculationDuplicateReport($this, $items);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[Route(path: '', name: 'duplicate_table')]
    public function table(Request $request, CalculationDuplicateTable $table, LoggerInterface $logger): Response
    {
        return $this->handleTableRequest($request, $table, 'calculation/calculation_table_duplicate.html.twig', $logger);
    }

    /**
     * Returns a response if no item is duplicated.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function getEmptyResponse(CalculationRepository $repository): ?RedirectResponse
    {
        if (0 === $repository->countItemsDuplicate()) {
            return $this->redirectToHomePage('duplicate.empty', [], FlashType::WARNING);
        }

        return null;
    }

    /**
     * Gets items to display.
     *
     * @psalm-return array<int, array{
     *      id: int,
     *      date: \DateTimeInterface,
     *      stateCode: string,
     *      customer: string,
     *      description: string,
     *      items: array<array{
     *          description: string,
     *          quantity: float,
     *          price: float,
     *          count: int}>
     *      }>
     */
    private function getItems(CalculationRepository $repository): array
    {
        return $repository->getItemsDuplicate();
    }
}
