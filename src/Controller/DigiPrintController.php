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

use App\DataTable\DigiPrintDataTable;
use App\Entity\AbstractEntity;
use App\Entity\DigiPrint;
use App\Excel\ExcelResponse;
use App\Form\Digiprint\DigiPrintServiceType;
use App\Form\Digiprint\DigiPrintType;
use App\Report\DigiPrintsReport;
use App\Repository\DigiPrintRepository;
use App\Service\DigiPrintService;
use App\Spreadsheet\DigiPrintDocument;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for DigiPrint entities.
 *
 * @see \App\Entity\DigiPrint
 *
 * @Route("/digiprint")
 * @IsGranted("ROLE_USER")
 */
class DigiPrintController extends AbstractEntityController
{
    /**
     * The list route.
     */
    private const ROUTE_LIST = 'digiprint_list';

    /**
     * The table route.
     */
    private const ROUTE_TABLE = 'digiprint_table';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(DigiPrint::class);
    }

    /**
     * Add a DigiPrint.
     *
     * @Route("/add", name="digiprint_add", methods={"GET", "POST"})
     */
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new DigiPrint());
    }

    /**
     * List the categories.
     *
     * @Route("", name="digiprint_list", methods={"GET"})
     */
    public function card(Request $request): Response
    {
        return $this->renderCard($request, 'format');
    }

    /**
     * Display the form to compute a DigiPrint.
     *
     * @Route("/compute", name="digiprint_compute", methods={"GET", "POST"})
     */
    public function compute(Request $request, DigiPrintService $service): Response
    {
        $form = $this->createForm(DigiPrintServiceType::class, $service);
        if ($this->handleRequestForm($request, $form)) {
            $service->compute();
        }

        return $this->render('digiprint/digiprint_compute.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Delete a product.
     *
     * @Route("/delete/{id}", name="digiprint_delete", requirements={"id": "\d+" })
     */
    public function delete(Request $request, DigiPrint $item): Response
    {
        $parameters = [
            'title' => 'digiprint.delete.title',
            'message' => 'digiprint.delete.message',
            'success' => 'digiprint.delete.success',
            'failure' => 'digiprint.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $parameters);
    }

    /**
     * Edit a DigiPrint.
     *
     * @Route("/edit/{id}", name="digiprint_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function edit(Request $request, DigiPrint $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export DigiPrint to an Excel document.
     *
     * @Route("/excel", name="digiprint_excel")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no DigiPrint is found
     */
    public function excel(): ExcelResponse
    {
        /** @var DigiPrint[] $entities */
        $entities = $this->getEntities('format');
        if (empty($entities)) {
            $message = $this->trans('digiprint.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new DigiPrintDocument($this, $entities);

        return $this->renderExcelDocument($doc);
    }

    /**
     * Export DigiPrint to a PDF document.
     *
     * @Route("/pdf", name="digiprint_pdf")
     */
    public function pdf(DigiPrintRepository $repository): Response
    {
        /** @var DigiPrint[] $entities */
        $entities = $this->getEntities('format');
        if (empty($entities)) {
            $message = $this->trans('digiprint.list.empty');
            throw new NotFoundHttpException($message);
        }

        $report = new DigiPrintsReport($this, $entities);

        return $this->renderPdfDocument($report);
    }

    /**
     * Show properties of a DigiPrint.
     *
     * @Route("/show/{id}", name="digiprint_show", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function show(DigiPrint $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("/table", name="digiprint_table", methods={"GET", "POST"})
     */
    public function table(Request $request, DigiPrintDataTable $table): Response
    {
        return $this->renderTable($request, $table);
    }

    /**
     * {@inheritdoc}
     *
     * @param DigiPrint $item
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        /** @var DigiPrint $item */
        $parameters = [
            'success' => $item->isNew() ? 'digiprint.add.success' : 'digiprint.edit.success',
            'item_index' => $item->getItems()->count(),
            'count_prices' => $item->getItemsPrice()->count(),
            'count_blackits' => $item->getItemsBacklit()->count(),
            'count_replicatings' => $item->getItemsReplicating()->count(),
        ];

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCardTemplate(): string
    {
        return 'digiprint/digiprint_card.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultRoute(): string
    {
        return $this->isDisplayTabular() ? self::ROUTE_TABLE : self::ROUTE_LIST;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditFormType(): string
    {
        return DigiPrintType::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditTemplate(): string
    {
        return 'digiprint/digiprint_edit.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getShowTemplate(): string
    {
        return 'digiprint/digiprint_show.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTableTemplate(): string
    {
        return 'digiprint/digiprint_table.html.twig';
    }
}
