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
use App\Attribute\GetDelete;
use App\Attribute\GetPost;
use App\Entity\Group;
use App\Interfaces\RoleInterface;
use App\Report\GroupsReport;
use App\Repository\CalculationGroupRepository;
use App\Repository\GroupRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\GroupsDocument;
use App\Table\DataQuery;
use App\Table\GroupTable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for group entities.
 *
 * @template-extends AbstractEntityController<Group, GroupRepository>
 */
#[AsController]
#[Route(path: '/group')]
#[IsGranted(RoleInterface::ROLE_USER)]
class GroupController extends AbstractEntityController
{
    public function __construct(GroupRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a group.
     */
    #[GetPost(path: '/add', name: 'group_add')]
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new Group());
    }

    /**
     * Clone (copy) a group.
     */
    #[GetPost(path: '/clone/{id}', name: 'group_clone', requirements: self::ID_REQUIREMENT)]
    public function clone(Request $request, Group $item): Response
    {
        $code = $this->trans('common.clone_description', ['%description%' => $item->getCode()]);
        $clone = $item->clone($code);
        $parameters = [
            'title' => 'group.clone.title',
            'params' => ['id' => $item->getId()],
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Delete a group.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[GetDelete(path: '/delete/{id}', name: 'group_delete', requirements: self::ID_REQUIREMENT)]
    public function delete(Request $request, Group $item, CalculationGroupRepository $groupRepository, LoggerInterface $logger): Response
    {
        // external references?
        $categories = $item->countCategories();
        $calculations = $groupRepository->countGroupReferences($item);
        if (0 !== $categories || 0 !== $calculations) {
            $items = [];
            if (0 !== $categories) {
                $items[] = $this->trans('counters.categories', ['count' => $categories]);
            }
            if (0 !== $calculations) {
                $items[] = $this->trans('counters.calculations', ['count' => $calculations]);
            }
            $message = $this->trans('group.delete.failure', ['%name%' => $item->getDisplay()]);

            $parameters = [
                'title' => 'group.delete.title',
                'message' => $message,
                'item' => $item,
                'items' => $items,
                'back_page' => $this->getDefaultRoute(),
                'back_text' => 'common.button_back_list',
            ];
            $this->updateQueryParameters($request, $parameters, $item);

            return $this->render('cards/card_warning.html.twig', $parameters);
        }

        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Edit a group.
     */
    #[GetPost(path: '/edit/{id}', name: 'group_edit', requirements: self::ID_REQUIREMENT)]
    public function edit(Request $request, Group $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the groups to a Spreadsheet document.
     *
     * @throws NotFoundHttpException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Get(path: '/excel', name: 'group_excel')]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('code');
        if ([] === $entities) {
            throw $this->createNotFoundException($this->trans('group.list.empty'));
        }
        $doc = new GroupsDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export the groups to a PDF document.
     *
     * @throws NotFoundHttpException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Get(path: '/pdf', name: 'group_pdf')]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities('code');
        if ([] === $entities) {
            throw $this->createNotFoundException($this->trans('group.list.empty'));
        }
        $doc = new GroupsReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a group.
     */
    #[Get(path: '/show/{id}', name: 'group_show', requirements: self::ID_REQUIREMENT)]
    public function show(Group $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     */
    #[Get(path: '', name: 'group_table')]
    public function table(
        GroupTable $table,
        LoggerInterface $logger,
        #[MapQueryString]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'group/group_table.html.twig');
    }
}
