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
use App\Enums\EntityName;
use App\Enums\EntityPermission;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract controller for entities management.
 *
 * @template T of AbstractEntity
 */
abstract class AbstractEntityController extends AbstractController
{
    use TableTrait;

    /**
     * The entity class name.
     *
     * @var class-string<T>
     */
    private readonly string $className;

    /**
     * The lower case entity class name.
     */
    private readonly string $lowerName;

    /**
     * Constructor.
     *
     * @param AbstractRepository<T> $repository the repository
     */
    public function __construct(protected readonly AbstractRepository $repository)
    {
        $this->className = $this->repository->getClassName();
        $this->lowerName = \strtolower(StringUtils::getShortName($this->className));
    }

    /**
     * Throws an exception unless the given attribute is granted against
     * the current authentication token and this entity class name.
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException if the access is denied
     */
    protected function checkPermission(EntityPermission ...$permissions): void
    {
        $subject = EntityName::tryFindValue($this->className);
        if (null === $subject) {
            return;
        }
        foreach ($permissions as $permission) {
            $this->denyAccessUnlessGranted($permission, $subject);
        }
    }

    /**
     * Delete an entity.
     *
     * @param request         $request the request
     * @param AbstractEntity  $item    the entity to delete
     * @param LoggerInterface $logger  the logger to log any exception
     *
     * @psalm-param T $item
     */
    protected function deleteEntity(Request $request, AbstractEntity $item, LoggerInterface $logger, array $parameters = []): Response
    {
        $this->checkPermission(EntityPermission::DELETE);
        $options = [
            'method' => Request::METHOD_DELETE,
            'csrf_field_name' => 'delete_token',
            'csrf_token_id' => $this->getDeleteToken($item),
        ];
        $parameters['item'] = $item;
        $form = $this->createForm(FormType::class, [], $options);
        if ($this->handleRequestForm($request, $form)) {
            try {
                $this->deleteFromDatabase($item);
            } catch (\Exception $e) {
                $id = $this->getMessageId('.delete.failure', 'common.delete_failure');

                return $this->renderFormException($id, $e, $logger);
            }
            $route = (string) ($parameters['route'] ?? $this->getDefaultRoute());

            return $this->getUrlGenerator()->redirect($request, null, $route);
        }

        $parameters['form'] = $form;
        $parameters['title'] = $this->getMessageId('.delete.title', 'common.delete_title');
        $parameters['message'] = $this->getMessageTrans($item, '.delete.message', 'common.delete_message');
        $this->updateQueryParameters($request, $parameters, $item->getId());

        return $this->render('cards/card_delete.html.twig', $parameters);
    }

    /**
     * This function delete the given entity from the database.
     *
     * @param AbstractEntity $item the entity to delete
     *
     * @psalm-param T $item
     */
    protected function deleteFromDatabase(AbstractEntity $item): void
    {
        $this->repository->remove($item);
    }

    /**
     * Edit an entity.
     *
     * @param request        $request    the request
     * @param AbstractEntity $item       the entity to edit
     * @param array          $parameters the optional parameters
     *
     * @psalm-param T $item
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        $isNew = $item->isNew();
        $permission = $isNew ? EntityPermission::ADD : EntityPermission::EDIT;
        $this->checkPermission($permission);
        $type = $this->getEditFormType();
        $form = $this->createForm($type, $item);
        if ($this->handleRequestForm($request, $form)) {
            $this->saveToDatabase($item);
            $route = (string) ($parameters['route'] ?? $this->getDefaultRoute());

            return $this->getUrlGenerator()->redirect($request, $item, $route);
        }

        $parameters['new'] = $isNew;
        $parameters['item'] = $item;
        $parameters['form'] = $form;
        $this->updateQueryParameters($request, $parameters, (int) $item->getId());

        // show form
        return $this->render($this->getEditTemplate(), $parameters);
    }

    /**
     * Gets the default route name used to display the list of entities.
     */
    protected function getDefaultRoute(): string
    {
        return \sprintf('%s_table', $this->lowerName);
    }

    /**
     * Gets the form type (class name) used to edit an entity.
     *
     * @return class-string<\Symfony\Component\Form\FormTypeInterface>
     *
     * @phpstan-return class-string<\Symfony\Component\Form\FormTypeInterface<T>>
     */
    abstract protected function getEditFormType(): string;

    /**
     * Gets the Twig template (path) name used to edit an entity.
     */
    protected function getEditTemplate(): string
    {
        return \sprintf('%1$s/%1$s_edit.html.twig', $this->lowerName);
    }

    /**
     * Gets the entities to display.
     *
     * @param ?string                $field    the optional sorted field
     * @param string                 $mode     the optional sort mode ("ASC" or "DESC")
     * @param array<Criteria|string> $criteria the filter criteria
     * @param literal-string         $alias    the entity alias
     *
     * @return AbstractEntity[] the entities
     *
     * @psalm-return T[]
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function getEntities(string $field = null, string $mode = Criteria::ASC, array $criteria = [], string $alias = AbstractRepository::DEFAULT_ALIAS): array
    {
        $sortedFields = null !== $field ? [$field => $mode] : [];

        /** @psalm-var \Doctrine\ORM\Query<int, T> $query */
        $query = $this->repository
            ->getSearchQuery($sortedFields, $criteria, $alias);

        return $query->getResult();
    }

    /**
     * Gets the Twig template (path) name used to show an entity.
     */
    protected function getShowTemplate(): string
    {
        return \sprintf('%1$s/%1$s_show.html.twig', $this->lowerName);
    }

    /**
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException if the access is denied
     */
    protected function renderPdfDocument(PdfDocument $doc, bool $inline = true, string $name = ''): PdfResponse
    {
        $this->checkPermission(EntityPermission::EXPORT);

        return parent::renderPdfDocument($doc, $inline, $name);
    }

    /**
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException if the access is denied
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function renderSpreadsheetDocument(SpreadsheetDocument $doc, bool $inline = true, string $name = ''): SpreadsheetResponse
    {
        $this->checkPermission(EntityPermission::EXPORT);

        return parent::renderSpreadsheetDocument($doc, $inline, $name);
    }

    /**
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException if the access is denied
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    protected function renderWordDocument(WordDocument $doc, bool $inline = true, string $name = ''): WordResponse
    {
        $this->checkPermission(EntityPermission::EXPORT);

        return parent::renderWordDocument($doc, $inline, $name);
    }

    /**
     * This function save the given entity to the database.
     *
     * Derived class can update entity before it is saved to the database.
     *
     * @param AbstractEntity $item the entity to save
     *
     * @psalm-param T $item
     */
    protected function saveToDatabase(AbstractEntity $item): void
    {
        if ($item->isNew()) {
            $this->repository->add($item);
        } else {
            $this->repository->flush();
        }
    }

    /**
     * Show properties of an entity.
     *
     * @param AbstractEntity $item       the entity to show
     * @param array          $parameters the additional parameters to pass to the template
     *
     * @throws \Symfony\Component\Finder\Exception\AccessDeniedException if the access is denied
     *
     * @psalm-param T $item
     */
    protected function showEntity(AbstractEntity $item, array $parameters = []): Response
    {
        $this->checkPermission(EntityPermission::SHOW);
        $parameters['item'] = $item;

        return $this->render($this->getShowTemplate(), $parameters);
    }

    /**
     * Update the parameters by adding the request query values.
     *
     * @param Request $request    the request to get the query values
     * @param array   $parameters the parameters to update
     * @param ?int    $id         an optional entity identifier
     */
    protected function updateQueryParameters(Request $request, array &$parameters, ?int $id = 0): void
    {
        /** @psalm-var array $existing */
        $existing = $parameters['params'] ?? [];
        $queryParameters = $request->query->all();
        $parameters['params'] = $existing + $queryParameters;
        if (!empty($id) && !isset($parameters['params']['id'])) {
            $parameters['params']['id'] = $id;
        }
    }

    /**
     * Gets delete token identifier for the given entity.
     */
    private function getDeleteToken(AbstractEntity $entity): string
    {
        return \sprintf('delete_%d', (int) $entity->getId());
    }

    private function getMessageId(string $suffix, string $default): string
    {
        $id = $this->lowerName . $suffix;

        return $this->isTransDefined($id) ? $id : $default;
    }

    private function getMessageTrans(AbstractEntity $entity, string $suffix, string $default): string
    {
        $id = $this->getMessageId($suffix, $default);
        $params = ['%name%' => $entity->getDisplay()];

        return $this->trans($id, $params);
    }
}
