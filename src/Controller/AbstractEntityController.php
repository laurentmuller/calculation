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

use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Interfaces\EntityInterface;
use App\Interfaces\SortModeInterface;
use App\Pdf\PdfDocument;
use App\Repository\AbstractRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Response\WordResponse;
use App\Spreadsheet\SpreadsheetDocument;
use App\Traits\TableTrait;
use App\Utils\StringUtils;
use App\Word\WordDocument;
use Doctrine\Common\Collections\Criteria;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract controller for entities management.
 *
 * @template TEntity of EntityInterface
 * @template TRepository of AbstractRepository<TEntity>
 */
abstract class AbstractEntityController extends AbstractController
{
    use TableTrait;

    /**
     * The entity class name.
     *
     * @var class-string<TEntity>
     */
    private readonly string $className;

    /**
     * The lower case entity class name without name space.
     */
    private readonly string $lowerName;

    /**
     * The entity class name without name space.
     */
    private readonly string $shortName;

    /**
     * @psalm-param TRepository $repository
     */
    public function __construct(private readonly AbstractRepository $repository)
    {
        $this->className = $this->repository->getClassName();
        $this->shortName = StringUtils::getShortName($this->className);
        $this->lowerName = \strtolower($this->shortName);
    }

    /**
     * Throws an exception unless the given attribute is granted against
     * the current authentication token and this entity class name.
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException if the access is denied
     */
    protected function checkPermission(EntityPermission ...$permissions): void
    {
        $entity = EntityName::tryFromMixed($this->className);
        if (!$entity instanceof EntityName) {
            return;
        }
        foreach ($permissions as $permission) {
            $this->denyAccessUnlessGranted($permission, $entity);
        }
    }

    /**
     * Delete an entity.
     *
     * @psalm-param TEntity $item
     * @psalm-param array{route?: string, ...} $parameters
     */
    protected function deleteEntity(
        Request $request,
        EntityInterface $item,
        LoggerInterface $logger,
        array $parameters = []
    ): Response {
        $this->checkPermission(EntityPermission::DELETE);
        $options = [
            'method' => Request::METHOD_DELETE,
            'csrf_field_name' => 'delete_token',
            'csrf_token_id' => $this->getDeleteToken($item),
        ];
        $form = $this->createForm(FormType::class, [], $options);
        if ($this->handleRequestForm($request, $form)) {
            try {
                $this->deleteFromDatabase($item);
            } catch (\Exception $e) {
                $id = $this->getMessageId('.delete.failure', 'common.delete_failure');

                return $this->renderFormException($id, $e, $logger);
            }

            return $this->redirectToDefaultRoute(request: $request, route: $parameters['route'] ?? null);
        }

        $parameters = \array_merge([
            'item' => $item,
            'form' => $form,
            'title' => $this->getMessageId('.delete.title', 'common.delete_title'),
            'message' => $this->getMessageId('.delete.message', 'common.delete_message'),
            'message_parameters' => ['%name%' => $item],
        ], $parameters);
        $this->updateQueryParameters($request, $parameters, $item);

        return $this->render('cards/card_delete.html.twig', $parameters);
    }

    /**
     * This function deletes the given entity from the database.
     *
     * @psalm-param TEntity $item
     */
    protected function deleteFromDatabase(EntityInterface $item): void
    {
        $this->repository->remove($item);
    }

    /**
     * Edit an entity.
     *
     * @psalm-param TEntity $item
     * @psalm-param array{route?: string, ...} $parameters
     */
    protected function editEntity(Request $request, EntityInterface $item, array $parameters = []): Response
    {
        $isNew = $item->isNew();
        $this->checkPermission($isNew ? EntityPermission::ADD : EntityPermission::EDIT);
        $type = $this->getEditFormType();
        $form = $this->createForm($type, $item);
        if ($this->handleRequestForm($request, $form)) {
            $this->saveToDatabase($item);

            return $this->redirectToDefaultRoute($request, $item, $parameters['route'] ?? null);
        }

        $parameters = \array_merge([
            'new' => $isNew,
            'item' => $item,
            'form' => $form,
            'submit_text' => $isNew ? 'common.button_submit_add' : 'common.button_submit_edit',
        ], $parameters);
        $this->updateQueryParameters($request, $parameters, $item);

        return $this->render($this->getEditTemplate(), $parameters);
    }

    /**
     * Gets the default route name used to display the list of entities.
     */
    protected function getDefaultRoute(): string
    {
        return \sprintf('%s_index', $this->lowerName);
    }

    /**
     * Gets the form type class name used to edit an entity.
     */
    protected function getEditFormType(): string
    {
        return \sprintf('App\\Form\\%1$s\\%1$sType', $this->shortName);
    }

    /**
     * Gets the template name used to edit an entity.
     */
    protected function getEditTemplate(): string
    {
        return \sprintf('%1$s/%1$s_edit.html.twig', $this->lowerName);
    }

    /**
     * Gets the entities to display.
     *
     * @param array<string, string>|string $sortedFields the sorted fields where key is the field name and value is
     *                                                   the sort mode ('ASC' or 'DESC') or a string for a single
     *                                                   ascending sorted field
     * @param array<Criteria|string>       $criteria     the filter criteria
     * @param literal-string               $alias        the entity alias
     *
     * @return TEntity[] the entities
     *
     * @psalm-return TEntity[]
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function getEntities(
        array|string $sortedFields = [],
        array $criteria = [],
        string $alias = AbstractRepository::DEFAULT_ALIAS
    ): array {
        if (\is_string($sortedFields)) {
            $sortedFields = [$sortedFields => SortModeInterface::SORT_ASC];
        }

        return $this->repository->getSearchQuery($sortedFields, $criteria, $alias)
            ->getResult();
    }

    /**
     * @psalm-return TRepository
     */
    protected function getRepository(): AbstractRepository
    {
        return $this->repository;
    }

    /**
     * Gets the template name used to show an entity.
     */
    protected function getShowTemplate(): string
    {
        return \sprintf('%1$s/%1$s_show.html.twig', $this->lowerName);
    }

    /**
     * Redirect to the default route.
     */
    protected function redirectToDefaultRoute(
        Request $request,
        EntityInterface|int|null $item = 0,
        ?string $route = null,
        int $status = Response::HTTP_FOUND,
    ): RedirectResponse {
        return $this->getUrlGenerator()
            ->redirect($request, $item, $route ?? $this->getDefaultRoute(), $status);
    }

    /**
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    protected function renderPdfDocument(PdfDocument $doc, bool $inline = true, string $name = ''): PdfResponse
    {
        $this->checkPermission(EntityPermission::EXPORT);

        return parent::renderPdfDocument($doc, $inline, $name);
    }

    /**
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function renderSpreadsheetDocument(
        SpreadsheetDocument $doc,
        bool $inline = true,
        string $name = ''
    ): SpreadsheetResponse {
        $this->checkPermission(EntityPermission::EXPORT);

        return parent::renderSpreadsheetDocument($doc, $inline, $name);
    }

    /**
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    protected function renderWordDocument(WordDocument $doc, bool $inline = true, string $name = ''): WordResponse
    {
        $this->checkPermission(EntityPermission::EXPORT);

        return parent::renderWordDocument($doc, $inline, $name);
    }

    /**
     * This function saves the given entity to the database.
     *
     * Derived class can update the entity before it is saved to the database.
     *
     * @psalm-param TEntity $item
     */
    protected function saveToDatabase(EntityInterface $item): void
    {
        if ($item->isNew()) {
            $this->repository->persist($item);
        } else {
            $this->repository->flush();
        }
    }

    /**
     * Show properties of an entity.
     *
     * @throws \Symfony\Component\Finder\Exception\AccessDeniedException if the access is denied
     *
     * @psalm-param TEntity $item
     */
    protected function showEntity(EntityInterface $item, array $parameters = []): Response
    {
        $this->checkPermission(EntityPermission::SHOW);
        $parameters['item'] = $item;

        return $this->render($this->getShowTemplate(), $parameters);
    }

    /**
     * Update the parameters by adding the request query values.
     *
     * @psalm-param array{params?: array, ...} $parameters
     */
    protected function updateQueryParameters(
        Request $request,
        array &$parameters,
        EntityInterface|int|null $id = null
    ): void {
        $params = \array_merge($request->query->all(), $parameters['params'] ?? []);
        if (!isset($params['id'])) {
            if ($id instanceof EntityInterface) {
                $id = $id->getId();
            }
            if (null !== $id && 0 !== $id) {
                $params['id'] = $id;
            }
        }
        $parameters['params'] = $params;
    }

    /**
     * Gets delete token identifier for the given entity.
     */
    private function getDeleteToken(EntityInterface $entity): string
    {
        return \sprintf('delete_%d', (int) $entity->getId());
    }

    private function getMessageId(string $suffix, string $default): string
    {
        $id = $this->lowerName . $suffix;

        return $this->isTransDefined($id) ? $id : $default;
    }
}
