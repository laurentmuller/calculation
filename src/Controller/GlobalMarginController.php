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

use App\DataTable\GlobalMarginDataTable;
use App\Entity\AbstractEntity;
use App\Entity\GlobalMargin;
use App\Excel\ExcelResponse;
use App\Form\GlobalMargin\GlobalMarginType;
use App\Pdf\PdfResponse;
use App\Report\GlobalMarginsReport;
use App\Spreadsheet\GlobalMarginDocument;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for global margins entities.
 *
 * @Route("/globalmargin")
 * @IsGranted("ROLE_USER")
 */
class GlobalMarginController extends AbstractEntityController
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(GlobalMargin::class);
    }

    /**
     * Add a global margin.
     *
     * @Route("/add", name="globalmargin_add", methods={"GET", "POST"})
     */
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new GlobalMargin());
    }

    /**
     * List the global margins.
     *
     * @Route("", name="globalmargin_list", methods={"GET"})
     */
    public function card(Request $request): Response
    {
        return $this->renderCard($request, 'minimum');
    }

    /**
     * Delete a global margin.
     *
     * @Route("/delete/{id}", name="globalmargin_delete", requirements={"id": "\d+" })
     */
    public function delete(Request $request, GlobalMargin $item): Response
    {
        $parameters = [
            'title' => 'globalmargin.delete.title',
            'message' => 'globalmargin.delete.message',
            'success' => 'globalmargin.delete.success',
            'failure' => 'globalmargin.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $parameters);
    }

    /**
     * Edit a global margin.
     *
     * @Route("/edit/{id}", name="globalmargin_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function edit(Request $request, GlobalMargin $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the global margins to an Excel document.
     *
     * @Route("/excel", name="globalmargin_excel")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no global margin is found
     */
    public function excel(): ExcelResponse
    {
        /** @var GlobalMargin[] $entities */
        $entities = $this->getEntities('minimum');
        if (empty($entities)) {
            $message = $this->trans('globalmargin.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new GlobalMarginDocument($this, $entities);

        return $this->renderExcelDocument($doc);
    }

    /**
     * Export the global margins to a PDF document.
     *
     * @Route("/pdf", name="globalmargin_pdf")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no global margin is found
     */
    public function pdf(): PdfResponse
    {
        /** @var GlobalMargin[] $entities */
        $entities = $this->getEntities('minimum');
        if (empty($entities)) {
            $message = $this->trans('globalmargin.list.empty');
            throw $this->createNotFoundException($message);
        }

        $report = new GlobalMarginsReport($this, $entities);

        return $this->renderPdfDocument($report);
    }

    /**
     * Show properties of a global margin.
     *
     * @Route("/show/{id}", name="globalmargin_show", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function show(GlobalMargin $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("/table", name="globalmargin_table", methods={"GET", "POST"})
     */
    public function table(Request $request, GlobalMarginDataTable $table): Response
    {
        return $this->renderTable($request, $table);
    }

    /**
     * {@inheritdoc}
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        // update parameters
        $parameters['success'] = $item->isNew() ? 'globalmargin.add.success' : 'globalmargin.edit.success';

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditFormType(): string
    {
        return GlobalMarginType::class;
    }
}
