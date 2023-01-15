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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for global margins entities.
 *
 * @template-extends AbstractEntityController<GlobalMargin>
 */
#[AsController]
#[Route(path: '/globalmargin')]
#[IsGranted(RoleInterface::ROLE_USER)]
class GlobalMarginController extends AbstractEntityController
{
    /**
     * Constructor.
     *
     * @throws \ReflectionException
     */
    public function __construct(GlobalMarginRepository $repository)
    {
        parent::__construct($repository);
    }

    #[Route(path: '/edit', name: 'globalmargin_edit')]
    public function edit(Request $request): Response
    {
        // check permissions
        $this->checkPermission(EntityPermission::ADD, EntityPermission::EDIT, EntityPermission::DELETE);

        /** @var GlobalMarginRepository $repository */
        $repository = $this->repository;
        $existingMargins = $repository->findAllByMinimum();
        $root = new GlobalMargins($existingMargins);

        $form = $this->createForm(GlobalMarginsType::class, $root);
        if ($this->handleRequestForm($request, $form)) {
            /** @var GlobalMargins $data */
            $data = $form->getData();
            $newMargins = $data->getMargins()->toArray();

            // update
            foreach ($newMargins as $margin) {
                $repository->add($margin, false);
            }
            // delete
            $deletedMargins = \array_diff($existingMargins, $newMargins);
            foreach ($deletedMargins as $margin) {
                $repository->remove($margin, false);
            }
            $repository->flush();
            $this->successTrans('globalmargin.edit.success');

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
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Psr\Container\ContainerExceptionInterface
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
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
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
    #[Route(path: '/show/{id}', name: 'globalmargin_show', requirements: ['id' => Requirement::DIGITS])]
    public function show(GlobalMargin $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @throws \ReflectionException
     */
    #[Route(path: '', name: 'globalmargin_table')]
    public function table(Request $request, GlobalMarginTable $table, LoggerInterface $logger): Response
    {
        return $this->handleTableRequest($request, $table, 'globalmargin/globalmargin_table.html.twig', $logger);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditFormType(): string
    {
        return GlobalMarginType::class;
    }
}
