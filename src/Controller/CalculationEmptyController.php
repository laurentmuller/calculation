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

use App\DataTable\CalculationEmptyDataTable;
use App\Report\CalculationEmptyReport;
use App\Repository\CalculationRepository;
use App\Spreadsheet\CalculationEmptyDocument;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for calculations where items has the price or the quantity is equal to 0.
 *
 * @author Laurent Muller
 *
 * @Route("/empty")
 * @IsGranted("ROLE_ADMIN")
 * @Breadcrumb({
 *     {"label" = "index.title", "route" = "homepage" },
 *     {"label" = "calculation.list.title", "route" = "table_empty", "params" = {
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
class CalculationEmptyController extends AbstractController
{
    /**
     * Shows calculations, as card, where items has the price or the quantity is equal to 0.
     *
     * @Route("/card", name="empty_card")
     */
    public function card(Request $request, CalculationRepository $repository): Response
    {
        if ($this->isEmptyItems($repository)) {
            $this->warningTrans('empty.empty');

            return $this->redirectToHomePage();
        }

        // number of items
        $items = $this->getItems($repository);
        $items_count = \array_reduce($items, function (int $carry, array $calculation) {
            return $carry + \count($calculation['items']);
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

        return $this->render('calculation/calculation_card_empty.html.twig', $parameters);
    }

    /**
     * Export the empty items to an Excel document.
     *
     * @Route("/excel", name="empty_excel")
     */
    public function excel(CalculationRepository $repository): Response
    {
        if ($this->isEmptyItems($repository)) {
            $this->warningTrans('empty.empty');

            return $this->redirectToHomePage();
        }

        $items = $this->getItems($repository);
        $doc = new CalculationEmptyDocument($this, $items);

        return $this->renderExcelDocument($doc);
    }

    /**
     * Export the calculations where items has the price or the quantity is equal to 0.
     *
     * @Route("/pdf", name="empty_pdf")
     */
    public function pdf(CalculationRepository $repository): Response
    {
        if ($this->isEmptyItems($repository)) {
            $this->warningTrans('empty.empty');

            return $this->redirectToHomePage();
        }

        $items = $this->getItems($repository);
        $doc = new CalculationEmptyReport($this, $items);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Shows calculations, as table, where items has the price or the quantity is equal to 0.
     *
     * @Route("", name="empty_table")
     */
    public function table(Request $request, CalculationEmptyDataTable $table, CalculationRepository $repository): Response
    {
        if (!$request->isXmlHttpRequest() && $this->isEmptyItems($repository)) {
            $this->warningTrans('empty.empty');

            return $this->redirectToHomePage();
        }

        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        // empty?
        $items = $this->getItems($repository);
        if (empty($items)) {
            $this->warningTrans('empty.empty');

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

        return $this->render('calculation/calculation_table_empty.html.twig', $parameters);
    }

    /**
     * Gets items to display.
     */
    private function getItems(CalculationRepository $repository): array
    {
        return $repository->getEmptyItems();
    }

    /**
     * Returns a value indicating if no item is empty.
     */
    private function isEmptyItems(CalculationRepository $repository): bool
    {
        return 0 === $repository->countEmptyItems();
    }
}
