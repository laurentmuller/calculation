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
use App\Spreadsheet\SpreadsheetDocument;
use App\Traits\TableTrait;
use App\Util\Utils;
use Doctrine\Common\Collections\Criteria;
use Psr\Log\LoggerInterface;
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
     */
    protected string $className;

    /**
     * The lower case entity class name.
     */
    protected string $lowerName;

    /**
     * Constructor.
     *
     * @param AbstractRepository<T> $repository the repository
     *
     * @throws \ReflectionException
     */
    public function __construct(protected readonly AbstractRepository $repository)
    {
        $this->className = $this->repository->getClassName();
        $this->lowerName = \strtolower(Utils::getShortName($this->className));
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
        foreach ($permissions as $permission) {
            $this->denyAccessUnlessGranted($permission, $subject);
        }
    }

    /**
     * Delete an entity.
     *
     * @param request         $request    the request
     * @param AbstractEntity  $item       the entity to delete
     * @param LoggerInterface $logger     the logger to log any exception
     * @param array           $parameters the optional parameters
     *
     * @psalm-param T $item
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \ReflectionException
     */
    protected function deleteEntity(Request $request, AbstractEntity $item, LoggerInterface $logger, array $parameters = []): Response
    {
        // check permission
        $this->checkPermission(EntityPermission::DELETE);

        // add item as parameter
        $parameters['item'] = $item;

        // create form and handle request
        $form = $this->createForm();
        if ($this->handleRequestForm($request, $form)) {
            try {
                // remove
                $this->deleteFromDatabase($item);
                // message
                $message = $this->getMessageTrans($item, '.delete.success', 'common.delete_success');
                $this->warning($message);
            } catch (\Exception $e) {
                $id = $this->getMessageId('.delete.failure', 'common.delete_failure');

                return $this->renderFormException($id, $e, $logger);
            }

            // redirect
            $id = 0;
            $route = (string) ($parameters['route'] ?? $this->getDefaultRoute());

            return $this->getUrlGenerator()->redirect($request, $id, $route);
        }

        // update parameters
        $parameters['form'] = $form;
        $parameters['title'] = $this->getMessageId('.delete.title', 'common.delete_title');
        $parameters['message'] = $this->getMessageTrans($item, '.delete.message', 'common.delete_message');
        $this->updateQueryParameters($request, $parameters, $item->getId());

        // show page
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
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        // check permission
        $isNew = $item->isNew();
        $permission = $isNew ? EntityPermission::ADD : EntityPermission::EDIT;
        $this->checkPermission($permission);

        // form
        $type = $this->getEditFormType();
        $form = $this->createForm($type, $item);
        if ($this->handleRequestForm($request, $form)) {
            // save
            $this->saveToDatabase($item);
            // message
            if ($isNew) {
                $message = $this->getMessageTrans($item, '.add.success', 'common.add_success');
            } else {
                $message = $this->getMessageTrans($item, '.edit.success', 'common.edit_success');
            }
            $this->success($message);
            // redirect
            $id = $item->getId();
            $route = (string) ($parameters['route'] ?? $this->getDefaultRoute());

            return $this->getUrlGenerator()->redirect($request, $id, $route);
        }

        // update parameters
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
     * @param string                 $alias    the entity alias
     *
     * @return AbstractEntity[] the entities
     *
     * @psalm-param literal-string $alias
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     *
     * @psalm-return T[]
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function getEntities(?string $field = null, string $mode = Criteria::ASC, array $criteria = [], string $alias = AbstractRepository::DEFAULT_ALIAS): array
    {
        $sortedFields = null !== $field ? [$field => $mode] : [];

        return $this->repository
            ->getSearchQuery($sortedFields, $criteria, $alias)
            ->getResult();
    }

    /**
     * Gets the Twig template (path) name used to show an entity.
     */
    protected function getShowTemplate(): string
    {
        return \sprintf('%1$s/%1$s_show.html.twig', $this->lowerName);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException if the access is denied
     */
    protected function renderPdfDocument(PdfDocument $doc, bool $inline = true, string $name = ''): PdfResponse
    {
        $this->checkPermission(EntityPermission::EXPORT);

        return parent::renderPdfDocument($doc, $inline, $name);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException if the access is denied
     */
    protected function renderSpreadsheetDocument(SpreadsheetDocument $doc, bool $inline = true, string $name = ''): SpreadsheetResponse
    {
        $this->checkPermission(EntityPermission::EXPORT);

        return parent::renderSpreadsheetDocument($doc, $inline, $name);
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
        // check permission
        $this->checkPermission(EntityPermission::SHOW);

        // add item parameter
        $parameters['item'] = $item;

        // render
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
