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

use App\Attribute\AddEntityRoute;
use App\Attribute\CloneEntityRoute;
use App\Attribute\DeleteEntityRoute;
use App\Attribute\EditEntityRoute;
use App\Attribute\ExcelRoute;
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Attribute\ShowEntityRoute;
use App\Entity\CalculationGroup;
use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Product;
use App\Entity\Task;
use App\Interfaces\RoleInterface;
use App\Report\GroupsReport;
use App\Repository\GroupRepository;
use App\Resolver\DataQueryValueResolver;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\GroupsDocument;
use App\Table\DataQuery;
use App\Table\GroupTable;
use App\Traits\ArrayTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for group entities.
 *
 * @extends AbstractEntityController<Group, GroupRepository>
 */
#[Route(path: '/group', name: 'group_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class GroupController extends AbstractEntityController
{
    use ArrayTrait;

    public function __construct(GroupRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Clone (copy) a group.
     */
    #[CloneEntityRoute]
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
    #[DeleteEntityRoute]
    public function delete(
        Request $request,
        Group $item,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): Response {
        $references = $this->countReferences($entityManager, $item);
        if ([] !== $references) {
            return $this->showDeleteWarning($item, $references);
        }

        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Add or edit a group.
     */
    #[AddEntityRoute]
    #[EditEntityRoute]
    public function edit(Request $request, ?Group $item): Response
    {
        return $this->editEntity($request, $item ?? new Group());
    }

    /**
     * Export the groups to a Spreadsheet document.
     */
    #[ExcelRoute]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('code');
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('group.list.empty');
        }

        return $this->renderSpreadsheetDocument(new GroupsDocument($this, $entities));
    }

    /**
     * Render the table view.
     */
    #[IndexRoute]
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
    #[PdfRoute]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities('code');
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('group.list.empty');
        }

        return $this->renderPdfDocument(new GroupsReport($this, $entities));
    }

    /**
     * Show properties of a group.
     */
    #[ShowEntityRoute]
    public function show(Group $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * @return array<string, int>
     */
    private function countReferences(EntityManagerInterface $entityManager, Group $item): array
    {
        return \array_filter([
            'counters.categories' => $entityManager->getRepository(Category::class)
                ->countGroupReferences($item),
            'counters.products' => $entityManager->getRepository(Product::class)
                ->countGroupReferences($item),
            'counters.tasks' => $entityManager->getRepository(Task::class)
                ->countGroupReferences($item),
            'counters.calculations' => $entityManager->getRepository(CalculationGroup::class)
                ->countGroupReferences($item),
        ]);
    }

    /**
     * @param array<string, int> $references
     */
    private function showDeleteWarning(Group $item, array $references): Response
    {
        $message = $this->trans('group.delete.failure', ['%name%' => $item]);
        $items = $this->mapKeyAndValue(
            $references,
            fn (string $id, int $count): string => $this->trans($id, ['count' => $count])
        );
        $parameters = [
            'title' => 'group.delete.title',
            'message' => $message,
            'item' => $item,
            'items' => $items,
            'back_page' => $this->getDefaultRoute(),
            'back_text' => 'common.button_back_list',
        ];

        return $this->render('cards/card_warning.html.twig', $parameters);
    }
}
