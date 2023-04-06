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

use App\Entity\Calculation;
use App\Enums\FlashType;
use App\Interfaces\RoleInterface;
use App\Report\CalculationsReport;
use App\Repository\CalculationRepository;
use App\Spreadsheet\CalculationsDocument;
use App\Table\CalculationBelowTable;
use App\Traits\TableTrait;
use App\Utils\FormatUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for calculations where margins are below the minimum.
 */
#[AsController]
#[Route(path: '/below')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class CalculationBelowController extends AbstractController
{
    use TableTrait;

    /**
     * Export the calculations to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if the report can not be rendered
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Route(path: '/excel', name: 'below_excel')]
    public function excel(CalculationRepository $repository): Response
    {
        $minMargin = $this->getMinMargin();
        if (($response = $this->getEmptyResponse($repository, $minMargin)) instanceof RedirectResponse) {
            return $response;
        }
        $items = $this->getItems($repository, $minMargin);
        $doc = new CalculationsDocument($this, $items);
        $doc->setTitle('below.title')
            ->setDescription($this->getDescription($minMargin));

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export calculations to a PDF document.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Route(path: '/pdf', name: 'below_pdf')]
    public function pdf(CalculationRepository $repository): Response
    {
        $minMargin = $this->getMinMargin();
        if (($response = $this->getEmptyResponse($repository, $minMargin)) instanceof RedirectResponse) {
            return $response;
        }
        $items = $this->getItems($repository, $minMargin);
        $doc = new CalculationsReport($this, $items);
        $doc->setTitleTrans('below.title')
            ->setDescription($this->getDescription($minMargin));

        return $this->renderPdfDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[Route(path: '', name: 'below_table')]
    public function table(Request $request, CalculationBelowTable $table, LoggerInterface $logger): Response
    {
        return $this->handleTableRequest($request, $table, 'calculation/calculation_table_below.html.twig', $logger);
    }

    private function getDescription(float $minMargin): string
    {
        return $this->trans('below.description', ['%margin%' => FormatUtils::formatPercent($minMargin)]);
    }

    /**
     * Returns a response if no calculation is below the given margin.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function getEmptyResponse(CalculationRepository $repository, float $minMargin): ?RedirectResponse
    {
        if (0 === $repository->countItemsBelow($minMargin)) {
            return $this->redirectToHomePage(message: 'below.empty', type: FlashType::WARNING);
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
