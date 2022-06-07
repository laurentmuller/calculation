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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for global margins entities.
 *
 * @template-extends AbstractEntityController<GlobalMargin>
 */
#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/globalmargin')]
class GlobalMarginController extends AbstractEntityController
{
    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator, GlobalMarginRepository $repository)
    {
        parent::__construct($translator, $repository);
    }

    /**
     * Add a global margin.
     */
    #[Route(path: '/add', name: 'globalmargin_add')]
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new GlobalMargin());
    }

    /**
     * Delete a global margin.
     */
    #[Route(path: '/delete/{id}', name: 'globalmargin_delete', requirements: ['id' => self::DIGITS])]
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
     */
    #[Route(path: '/edit/{id}', name: 'globalmargin_edit', requirements: ['id' => self::DIGITS])]
    public function edit(Request $request, GlobalMargin $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the global margins to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no global margin is found
     */
    #[Route(path: '/excel', name: 'globalmargin_excel')]
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
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no global margin is found
     */
    #[Route(path: '/pdf', name: 'globalmargin_pdf')]
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
     */
    #[Route(path: '/show/{id}', name: 'globalmargin_show', requirements: ['id' => self::DIGITS])]
    public function show(GlobalMargin $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     */
    #[Route(path: '', name: 'globalmargin_table')]
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
