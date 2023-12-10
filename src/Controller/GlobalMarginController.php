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

use App\Entity\GlobalMargin;
use App\Enums\EntityPermission;
use App\Form\GlobalMargin\GlobalMarginsType;
use App\Form\GlobalMargin\GlobalMarginType;
use App\Interfaces\RoleInterface;
use App\Model\GlobalMargins;
use App\Report\GlobalMarginsReport;
use App\Repository\GlobalMarginRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\GlobalMarginsDocument;
use App\Table\GlobalMarginTable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for global margins entities.
 *
 * @template-extends AbstractEntityController<GlobalMargin, GlobalMarginRepository>
 */
#[AsController]
#[Route(path: '/globalmargin')]
#[IsGranted(RoleInterface::ROLE_USER)]
class GlobalMarginController extends AbstractEntityController
{
    public function __construct(GlobalMarginRepository $repository)
    {
        parent::__construct($repository);
    }

    #[Route(path: '/edit', name: 'globalmargin_edit', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function edit(Request $request): Response
    {
        $this->checkPermission(EntityPermission::ADD, EntityPermission::EDIT, EntityPermission::DELETE);

        $repository = $this->getRepository();
        $existingMargins = $repository->findByMinimum();
        $root = new GlobalMargins($existingMargins);
        $form = $this->createForm(GlobalMarginsType::class, $root);
        if ($this->handleRequestForm($request, $form)) {
            $newMargins = $root->getMargins()->toArray();
            foreach ($newMargins as $margin) {
                $repository->persist($margin, false);
            }
            $deletedMargins = \array_diff($existingMargins, $newMargins);
            foreach ($deletedMargins as $margin) {
                $repository->remove($margin, false);
            }
            $repository->flush();

            return $this->redirectToRoute('globalmargin_table');
        }

        return $this->render('globalmargin/globalmargin_edit_list.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Export the global margins to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no global margin is found
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Route(path: '/excel', name: 'globalmargin_excel', methods: Request::METHOD_GET)]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('minimum');
        if ([] === $entities) {
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
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Route(path: '/pdf', name: 'globalmargin_pdf', methods: Request::METHOD_GET)]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities('minimum');
        if ([] === $entities) {
            $message = $this->trans('globalmargin.list.empty');
            throw $this->createNotFoundException($message);
        }
        $report = new GlobalMarginsReport($this, $entities);

        return $this->renderPdfDocument($report);
    }

    /**
     * Show properties of a global margin.
     */
    #[Route(path: '/show/{id}', name: 'globalmargin_show', requirements: ['id' => Requirement::DIGITS], methods: Request::METHOD_GET)]
    public function show(GlobalMargin $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     */
    #[Route(path: '', name: 'globalmargin_table', methods: Request::METHOD_GET)]
    public function table(Request $request, GlobalMarginTable $table, LoggerInterface $logger): Response
    {
        return $this->handleTableRequest(
            $request,
            $table,
            $logger,
            'globalmargin/globalmargin_table.html.twig'
        );
    }

    protected function getEditFormType(): string
    {
        return GlobalMarginType::class;
    }
}
