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
use App\Entity\Group;
use App\Form\Group\GroupType;
use App\Report\GroupsReport;
use App\Repository\CalculationGroupRepository;
use App\Repository\GroupRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\GroupsDocument;
use App\Table\GroupTable;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for group entities.
 *
 * @author Laurent Muller
 *
 * @Route("/group")
 * @IsGranted("ROLE_USER")
 * @template-extends AbstractEntityController<Group>
 */
class GroupController extends AbstractEntityController
{
    /**
     * Constructor.
     */
    public function __construct(GroupRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a group.
     *
     * @Route("/add", name="group_add")
     */
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new Group());
    }

    /**
     * Clone (copy) a group.
     *
     * @Route("/clone/{id}", name="group_clone", requirements={"id" = "\d+"})
     */
    public function clone(Request $request, Group $item): Response
    {
        $code = $this->trans('common.clone_description', ['%description%' => $item->getCode()]);
        $clone = $item->clone($code);
        $parameters = [
            'params' => ['id' => $item->getId()],
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Delete a group.
     *
     * @Route("/delete/{id}", name="group_delete", requirements={"id" = "\d+"})
     */
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
                'item' => $item,
                'id' => $item->getId(),
                'title' => 'group.delete.title',
                'message' => $message,
                'items' => $items,
                'back_page' => $this->getDefaultRoute(),
                'back_text' => 'common.button_back_list',
            ];
            $this->updateQueryParameters($request, $parameters, $item->getId());

            return $this->renderForm('cards/card_warning.html.twig', $parameters);
        }

        $parameters = [
            'title' => 'group.delete.title',
            'message' => 'group.delete.message',
            'success' => 'group.delete.success',
            'failure' => 'group.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $logger, $parameters);
    }

    /**
     * Edit a group.
     *
     * @Route("/edit/{id}", name="group_edit", requirements={"id" = "\d+"})
     */
    public function edit(Request $request, Group $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the groups to a Spreadsheet document.
     *
     * @Route("/excel", name="group_excel")
     *
     * @throws NotFoundHttpException if no group is found
     */
    public function excel(): SpreadsheetResponse
    {
        $groups = $this->getEntities('code');
        if (empty($groups)) {
            $message = $this->trans('group.list.empty');
            throw new NotFoundHttpException($message);
        }

        $doc = new GroupsDocument($this, $groups);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export the groups to a PDF document.
     *
     * @Route("/pdf", name="group_pdf")
     *
     * @throws NotFoundHttpException if no group is found
     */
    public function pdf(): PdfResponse
    {
        $groups = $this->getEntities('code');
        if (empty($groups)) {
            $message = $this->trans('group.list.empty');
            throw new NotFoundHttpException($message);
        }

        $doc = new GroupsReport($this, $groups);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a group.
     *
     * @Route("/show/{id}", name="group_show", requirements={"id" = "\d+"})
     */
    public function show(Group $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("", name="group_table")
     */
    public function table(Request $request, GroupTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'group/group_table.html.twig');
    }

    /**
     * {@inheritdoc}
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        // update parameters
        $parameters['success'] = $item->isNew() ? 'group.add.success' : 'group.edit.success';

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditFormType(): string
    {
        return GroupType::class;
    }
}
