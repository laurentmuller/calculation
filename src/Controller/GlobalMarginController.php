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

use App\Entity\AbstractEntity;
use App\Entity\GlobalMargin;
use App\Form\GlobalMargin\GlobalMarginType;
use App\Report\GlobalMarginsReport;
use App\Repository\GlobalMarginRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\GlobalMarginsDocument;
use App\Table\GlobalMarginTable;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for global margins entities.
 *
 * @author Laurent Muller
 *
 * @Route("/globalmargin")
 * @IsGranted("ROLE_USER")
 * @Breadcrumb({
 *     {"label" = "index.title", "route" = "homepage"}
 * })
 * @template-extends AbstractEntityController<GlobalMargin>
 */
class GlobalMarginController extends AbstractEntityController
{
    /**
     * Constructor.
     */
    public function __construct(GlobalMarginRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a global margin.
     *
     * @Route("/add", name="globalmargin_add")
     * @Breadcrumb({
     *     {"label" = "globalmargin.list.title", "route" = "globalmargin_table"},
     *     {"label" = "globalmargin.add.title"}
     * })
     */
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new GlobalMargin());
    }

    /**
     * Delete a global margin.
     *
     * @Route("/delete/{id}", name="globalmargin_delete", requirements={"id" = "\d+"})
     * @Breadcrumb({
     *     {"label" = "globalmargin.list.title", "route" = "globalmargin_table"},
     *     {"label" = "breadcrumb.delete"},
     *     {"label" = "$item.display"}
     * })
     */
    public function delete(Request $request, GlobalMargin $item, LoggerInterface $logger): Response
    {
        $parameters = [
            'title' => 'globalmargin.delete.title',
            'message' => 'globalmargin.delete.message',
            'success' => 'globalmargin.delete.success',
            'failure' => 'globalmargin.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $logger, $parameters);
    }

    /**
     * Edit a global margin.
     *
     * @Route("/edit/{id}", name="globalmargin_edit", requirements={"id" = "\d+"})
     * @Breadcrumb({
     *     {"label" = "globalmargin.list.title", "route" = "globalmargin_table"},
     *     {"label" = "breadcrumb.edit"},
     *     {"label" = "$item.display"}
     * })
     */
    public function edit(Request $request, GlobalMargin $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the global margins to a Spreadsheet document.
     *
     * @Route("/excel", name="globalmargin_excel")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no global margin is found
     */
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('minimum');
        if (empty($entities)) {
            $message = $this->trans('globalmargin.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new GlobalMarginsDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
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
     * @Route("/show/{id}", name="globalmargin_show", requirements={"id" = "\d+"})
     * @Breadcrumb({
     *     {"label" = "globalmargin.list.title", "route" = "globalmargin_table"},
     *     {"label" = "breadcrumb.property"},
     *     {"label" = "$item.display"}
     * })
     */
    public function show(GlobalMargin $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("", name="globalmargin_table")
     * @Breadcrumb({
     *     {"label" = "globalmargin.list.title"}
     * })
     */
    public function table(Request $request, GlobalMarginTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'globalmargin/globalmargin_table.html.twig');
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
