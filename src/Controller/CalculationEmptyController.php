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
use App\Report\CalculationEmptyReport;
use App\Repository\CalculationRepository;
use App\Spreadsheet\CalculationsEmptyDocument;
use App\Table\CalculationEmptyTable;
use App\Traits\TableTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for calculations where items have the price or the quantity is equal to 0.
 */
#[AsController]
#[Route(path: '/empty')]
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
    #[Route(path: '/excel', name: 'empty_excel')]
    public function excel(CalculationRepository $repository): Response
    {
        if (null !== $response = $this->getEmptyResponse($repository)) {
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
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/pdf', name: 'empty_pdf')]
    public function pdf(CalculationRepository $repository): Response
    {
        if (null !== $response = $this->getEmptyResponse($repository)) {
            return $response;
        }
        $items = $this->getItems($repository);
        $doc = new CalculationEmptyReport($this, $items);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[Route(path: '', name: 'empty_table')]
    public function table(Request $request, CalculationEmptyTable $table, LoggerInterface $logger): Response
    {
        return $this->handleTableRequest($request, $table, 'calculation/calculation_table_empty.html.twig', $logger);
    }

    /**
     * Returns a response if no calculation's item is empty.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function getEmptyResponse(CalculationRepository $repository): ?RedirectResponse
    {
        if (0 === $repository->countItemsEmpty()) {
            return $this->redirectToHomePage('empty.empty', [], FlashType::WARNING);
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
        return $repository->getItemsEmpty();
    }
}
