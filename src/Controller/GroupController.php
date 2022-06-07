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
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for group entities.
 *
 * @template-extends AbstractEntityController<Group>
 */
#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/group')]
class GroupController extends AbstractEntityController
{
    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator, GroupRepository $repository)
    {
        parent::__construct($translator, $repository);
    }

    /**
     * Add a group.
     */
    #[Route(path: '/add', name: 'group_add')]
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new Group());
    }

    /**
     * Clone (copy) a group.
     */
    #[Route(path: '/clone/{id}', name: 'group_clone', requirements: ['id' => self::DIGITS])]
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
     */
    #[Route(path: '/delete/{id}', name: 'group_delete', requirements: ['id' => self::DIGITS])]
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
     */
    #[Route(path: '/edit/{id}', name: 'group_edit', requirements: ['id' => self::DIGITS])]
    public function edit(Request $request, Group $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the groups to a Spreadsheet document.
     *
     * @throws NotFoundHttpException if no group is found
     */
    #[Route(path: '/excel', name: 'group_excel')]
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
     * @throws NotFoundHttpException if no group is found
     */
    #[Route(path: '/pdf', name: 'group_pdf')]
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
     */
    #[Route(path: '/show/{id}', name: 'group_show', requirements: ['id' => self::DIGITS])]
    public function show(Group $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     */
    #[Route(path: '', name: 'group_table')]
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
