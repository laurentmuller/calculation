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

use App\Entity\DigiPrint;
use App\Form\digiprint\DigiPrintType;
use App\Report\DigiPrintsReport;
use App\Repository\DigiPrintRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for DigiPrint entities.
 *
 * @see \App\Entity\DigiPrint
 *
 * @Route("/digiprint")
 * @IsGranted("ROLE_USER")
 */
class DigiPrintController extends AbstractController
{
    /**
     * Edit a category.
     *
     * @Route("/edit/{id}", name="digiprint_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function edit(Request $request, DigiPrint $item): Response
    {
        $form = $this->createForm(DigiPrintType::class, $item);
        if ($this->handleRequestForm($request, $form)) {
        }

        return $this->render('digiprint/digiprint_edit.html.twig', [
            'form' => $form->createView(),
            'new' => $item->isNew(),
        ]);
    }

    /**
     * Export DigiPrint to a PDF document.
     *
     * @Route("/pdf", name="digiprint_pdf")
     */
    public function pdf(DigiPrintRepository $repository): Response
    {
        $entities = $repository->findAllByFormat();
        if (empty($entities)) {
        }

        $report = new DigiPrintsReport($this, $entities);

        return $this->renderPdfDocument($report);
    }
}
