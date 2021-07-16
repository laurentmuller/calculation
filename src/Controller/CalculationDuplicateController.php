<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Controller;

use App\DataTable\CalculationDuplicateDataTable;
use App\Report\CalculationDuplicateReport;
use App\Repository\CalculationRepository;
use App\Spreadsheet\CalculationsDuplicateDocument;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to display and export duplicate items in the calculations.
 *
 * @author Laurent Muller
 *
 * @Route("/duplicate")
 * @IsGranted("ROLE_ADMIN")
 * @Breadcrumb({
 *     {"label" = "index.title", "route" = "homepage" },
 *     {"label" = "calculation.list.title", "route" = "table_duplicate", "params" = {
 *         "id" = "$params.[id]",
 *         "search" = "$params.[search]",
 *         "sort" = "$params.[sort]",
 *         "order" = "$params.[order]",
 *         "offset" = "$params.[offset]",
 *         "limit" = "$params.[limit]",
 *         "view" = "$params.[view]"
 *     }}
 * })
 */
class CalculationDuplicateController extends AbstractController
{
    /**
     * Shows duplicate items, as card, in the calculations.
     *
     * @Route("/card", name="duplicate_card")
     */
    public function card(Request $request, CalculationRepository $repository): Response
    {
        if ($this->isEmptyItems($repository)) {
            $this->warningTrans('duplicate.empty');

            return $this->redirectToHomePage();
        }

        // number of items
        $items = $this->getItems($repository);
        $items_count = \array_reduce($items, function (int $carry, array $calculation) {
            foreach ($calculation['items'] as $item) {
                $carry += $item['count'];
            }

            return $carry;
        }, 0);

        // parameters
        $parameters = [
                'items' => $items,
                'items_count' => $items_count,
                'query' => false,
                'id' => $request->get('id', 0),
                'sortField' => 'id',
                'sortMode' => Criteria::DESC,
                'sortFields' => [],
            ];

        return $this->renderForm('calculation/calculation_card_duplicate.html.twig', $parameters);
    }

    /**
     * Export the duplicate items to a Spreadsheet document.
     *
     * @Route("/excel", name="duplicate_excel")
     */
    public function excel(CalculationRepository $repository): Response
    {
        if ($this->isEmptyItems($repository)) {
            $this->warningTrans('duplicate.empty');

            return $this->redirectToHomePage();
        }

        $items = $this->getItems($repository);
        $doc = new CalculationsDuplicateDocument($this, $items);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Exports the duplicate items in the calculations.
     *
     * @Route("/pdf", name="duplicate_pdf")
     */
    public function pdf(CalculationRepository $repository): Response
    {
        if ($this->isEmptyItems($repository)) {
            $this->warningTrans('duplicate.empty');

            return $this->redirectToHomePage();
        }

        $items = $this->getItems($repository);
        $doc = new CalculationDuplicateReport($this, $items);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Display the duplicate items, as table, in the calculations.
     *
     * @Route("", name="duplicate_table")
     */
    public function table(Request $request, CalculationDuplicateDataTable $table, CalculationRepository $repository): Response
    {
        if (!$request->isXmlHttpRequest() && $this->isEmptyItems($repository)) {
            $this->warningTrans('duplicate.empty');

            return $this->redirectToHomePage();
        }

        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        $items = $this->getItems($repository);
        if (empty($items)) {
            $this->warningTrans('duplicate.empty');

            return $this->redirectToHomePage();
        }

        // attributes
        $attributes = [
            'itemsCount' => $table->getItemCounts(),
        ];

        // parameters
        $parameters = [
            'results' => $results,
            'attributes' => $attributes,
            'columns' => $table->getColumns(),
        ];

        return $this->renderForm('calculation/calculation_table_duplicate.html.twig', $parameters);
    }

    /**
     * Gets items to display.
     */
    private function getItems(CalculationRepository $repository): array
    {
        return $repository->getDuplicateItems();
    }

    /**
     * Returns a value indicating if no item is duplicated.
     */
    private function isEmptyItems(CalculationRepository $repository): bool
    {
        return 0 === $repository->countDuplicateItems();
    }
}
