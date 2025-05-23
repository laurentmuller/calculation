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
use App\Resolver\DataQueryValueResolver;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\GroupsDocument;
use App\Table\DataQuery;
use App\Table\GroupTable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for group entities.
 *
 * @template-extends AbstractEntityController<Group, GroupRepository>
 */
#[AsController]
#[Route(path: '/group', name: 'group_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class GroupController extends AbstractEntityController
{
    public function __construct(GroupRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Clone (copy) a group.
     */
    #[GetPost(path: self::CLONE_PATH, name: self::CLONE_NAME, requirements: self::ID_REQUIREMENT)]
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
     */
    #[GetDelete(path: self::DELETE_PATH, name: self::DELETE_NAME, requirements: self::ID_REQUIREMENT)]
    public function delete(
        Request $request,
        Group $item,
        CalculationGroupRepository $groupRepository,
        LoggerInterface $logger
    ): Response {
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
     * Add or edit a group.
     */
    #[GetPost(path: self::ADD_PATH, name: self::ADD_NAME)]
    #[GetPost(path: self::EDIT_PATH, name: self::EDIT_NAME, requirements: self::ID_REQUIREMENT)]
    public function edit(Request $request, ?Group $item): Response
    {
        return $this->editEntity($request, $item ?? new Group());
    }

    /**
     * Export the groups to a Spreadsheet document.
     */
    #[Get(path: self::EXCEL_PATH, name: self::EXCEL_NAME)]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('code');
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('group.list.empty');
        }
        $doc = new GroupsDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[Get(path: self::INDEX_PATH, name: self::INDEX_NAME)]
    public function index(
        GroupTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'group/group_table.html.twig');
    }

    /**
     * Export the groups to a PDF document.
     */
    #[Get(path: self::PDF_PATH, name: self::PDF_NAME)]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities('code');
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('group.list.empty');
        }
        $doc = new GroupsReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a group.
     */
    #[Get(path: self::SHOW_PATH, name: self::SHOW_NAME, requirements: self::ID_REQUIREMENT)]
    public function show(Group $item): Response
    {
        return $this->showEntity($item);
    }
}
