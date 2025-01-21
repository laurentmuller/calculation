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

use App\Attribute\Get;
use App\Attribute\GetPost;
use App\Entity\GlobalMargin;
use App\Enums\EntityPermission;
use App\Form\GlobalMargin\GlobalMarginsType;
use App\Interfaces\RoleInterface;
use App\Model\GlobalMargins;
use App\Report\GlobalMarginsReport;
use App\Repository\GlobalMarginRepository;
use App\Resolver\DataQueryValueResolver;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\GlobalMarginsDocument;
use App\Table\DataQuery;
use App\Table\GlobalMarginTable;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for global margins entities.
 *
 * @template-extends AbstractEntityController<GlobalMargin, GlobalMarginRepository>
 */
#[AsController]
#[Route(path: '/globalmargin', name: 'globalmargin_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class GlobalMarginController extends AbstractEntityController
{
    public function __construct(GlobalMarginRepository $repository)
    {
        parent::__construct($repository);
    }

    #[GetPost(path: '/edit', name: 'edit')]
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

            return $this->redirectToRoute('globalmargin_index');
        }

        return $this->render('globalmargin/globalmargin_edit_list.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Export the global margins to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no global margin is found
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws ORMException
     */
    #[Get(path: '/excel', name: 'excel')]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('minimum');
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('globalmargin.list.empty');
        }
        $doc = new GlobalMarginsDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[Get(path: '', name: 'index')]
    public function index(
        GlobalMarginTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'globalmargin/globalmargin_table.html.twig');
    }

    /**
     * Export the global margins to a PDF document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no global margin is found
     * @throws ORMException
     */
    #[Get(path: '/pdf', name: 'pdf')]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities('minimum');
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('globalmargin.list.empty');
        }
        $report = new GlobalMarginsReport($this, $entities);

        return $this->renderPdfDocument($report);
    }

    /**
     * Show properties of a global margin.
     */
    #[Get(path: '/show/{id}', name: 'show', requirements: self::ID_REQUIREMENT)]
    public function show(GlobalMargin $item): Response
    {
        return $this->showEntity($item);
    }
}
