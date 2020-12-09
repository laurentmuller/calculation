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

use App\DataTable\CalculationBelowDataTable;
use App\Report\CalculationsReport;
use App\Repository\CalculationRepository;
use App\Util\FormatUtils;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for calculations where margins are below the minimum.
 *
 * @author Laurent Muller
 *
 * @Route("/below")
 * @IsGranted("ROLE_ADMIN")
 */
class BelowCalculationController extends AbstractController
{
    /**
     * Shows calculations, as card, where margins is below the minimum.
     *
     * @Route("", name="below_card")
     */
    public function card(Request $request, CalculationRepository $repository): Response
    {
        // get values
        $minMargin = $this->getApplication()->getMinMargin();
        $items = $this->getItems($repository, $minMargin);
        if (empty($items)) {
            $this->warningTrans('below.empty');

            return  $this->redirectToHomePage();
        }

        // parameters
        $parameters = [
            'items' => $items,
            'items_count' => \count($items),
            'min_margin' => $minMargin,
            'query' => false,
            'id' => $request->get('id', 0),
            'sortField' => 'id',
            'sortMode' => Criteria::DESC,
            'sortFields' => [],
        ];

        return $this->render('calculation/calculation_card_below.html.twig', $parameters);
    }

    /**
     * Exports calculations where margins is below the minimum.
     *
     * @Route("/pdf", name="below_pdf")
     */
    public function pdf(CalculationRepository $repository): Response
    {
        $minMargin = $this->getApplication()->getMinMargin();
        $items = $this->getItems($repository, $minMargin);
        if (empty($items)) {
            $this->warningTrans('below.empty');

            return  $this->redirectToHomePage();
        }

        $percent = FormatUtils::formatPercent($minMargin);
        $description = $this->trans('below.description', ['%margin%' => $percent]);

        $doc = new CalculationsReport($this, $items);
        $doc->setTitleTrans('below.title')
            ->setDescription($description);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Shows calculations, as table, where margins is below the minimum.
     *
     * @Route("/table", name="below_table")
     */
    public function table(Request $request, CalculationBelowDataTable $table, CalculationRepository $repository): Response
    {
        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        // get values
        $minMargin = $this->getApplication()->getMinMargin();
        $items = $this->getItems($repository, $minMargin);
        if (empty($items)) {
            $this->warningTrans('below.empty');

            return  $this->redirectToHomePage();
        }

        // get values
        $margin_text = $this->trans('calculation.list.margin_below', ['%minimum%' => FormatUtils::formatPercent($minMargin)]);

        $attributes = [
            'min_margin' => $minMargin,
            'min_margin_text' => $margin_text,
        ];

        // parameters
        $parameters = [
            'results' => $results,
            'attributes' => $attributes,
            'columns' => $table->getColumns(),
        ];

        return $this->render('calculation/calculation_table_below.html.twig', $parameters);
    }

    /**
     * Gets items to display.
     */
    private function getItems(CalculationRepository $repository, float $minMargin): array
    {
        return $repository->getBelowMargin($minMargin);
    }
}
