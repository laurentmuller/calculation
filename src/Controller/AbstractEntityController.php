<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Controller;

use App\DataTable\Model\AbstractEntityDataTable;
use App\Entity\AbstractEntity;
use App\Interfaces\EntityVoterInterface;
use App\Pdf\PdfDocument;
use App\Repository\AbstractRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Security\EntityVoter;
use App\Spreadsheet\SpreadsheetDocument;
use App\Util\Utils;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract controller for entities management.
 *
 * @author Laurent Muller
 *
 * @template T of AbstractEntity
 */
abstract class AbstractEntityController extends AbstractController
{
    /**
     * The entity class name.
     */
    protected string $className;

    /**
     * The lower case entity class name.
     */
    protected string $lowerName;

    /**
     * The repository.
     *
     * @psalm-var AbstractRepository<T>
     */
    protected AbstractRepository $repository;

    /**
     * Constructor.
     *
     * @psalm-param AbstractRepository<T> $repository
     */
    public function __construct(AbstractRepository $repository)
    {
        $this->repository = $repository;
        $this->className = $repository->getClassName();
        $this->lowerName = \strtolower(Utils::getShortName($this->className));
    }

    /**
     * Throws an exception unless the given attribute is granted against
     * the current authentication token and this entity class name.
     *
     * @param string $attribute the attribute to check permission for
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException if the access is denied
     */
    protected function checkPermission(string $attribute): void
    {
        $subject = EntityVoter::getEntityName($this->className);
        $this->denyAccessUnlessGranted($attribute, $subject);
    }

    /**
     * Delete an entity.
     *
     * @param request         $request    the request
     * @param AbstractEntity  $item       the entity to delete
     * @param LoggerInterface $logger     the logger to log any exception
     * @param array           $parameters the delete parameters. The following optional keys may be added:
     *                                    <ul>
     *                                    <li><code>title</code> : the dialog title.</li>
     *                                    <li><code>message</code> : the dialog message.</li>
     *                                    <li><code>success</code> : the message to display on success.</li>
     *                                    <li><code>failure</code> : the message to display on failure.</li>
     *                                    </ul>
     * @psalm-param T $item
     */
    protected function deleteEntity(Request $request, AbstractEntity $item, LoggerInterface $logger, array $parameters = []): Response
    {
        // check permission
        $this->checkPermission(EntityVoterInterface::ATTRIBUTE_DELETE);

        // save display
        $display = $item->getDisplay();

        //add item as parameter
        $parameters['item'] = $item;

        // create form and handle request
        $form = $this->createForm();
        if ($this->handleRequestForm($request, $form)) {
            try {
                // remove
                $this->deleteFromDatabase($item);
                // message
                $message = (string) ($parameters['success'] ?? 'common.delete_success');
                $message = $this->trans($message, ['%name%' => $display]);
                $this->warning($message);
            } catch (Exception $e) {
                $failure = (string) ($parameters['failure'] ?? 'common.delete_failure');
                $message = $this->trans($failure, ['%name%' => $display]);
                $context = Utils::getExceptionContext($e);
                $logger->error($message, $context);

                return $this->renderForm('@Twig/Exception/exception.html.twig', [
                    'message' => $message,
                    'exception' => $e,
                ]);
            }

            // redirect
            $id = 0;
            $route = (string) ($parameters['route'] ?? $this->getDefaultRoute());

            return $this->getUrlGenerator()->redirect($request, $id, $route);
        }

        // get parameters
        $title = (string) ($parameters['title'] ?? 'common.delete_title');
        $message = (string) ($parameters['message'] ?? 'common.delete_message');
        $message = $this->trans($message, ['%name%' => $display]);

        // update parameters
        $parameters['title'] = $title;
        $parameters['message'] = $message;
        $parameters['form'] = $form;
        $this->updateQueryParameters($request, $parameters, $item->getId());

        // show page
        return $this->renderForm('cards/card_delete.html.twig', $parameters);
    }

    /**
     * This function delete the given entity from the database.
     *
     * @param AbstractEntity $item the entity to delete
     * @psalm-param T $item
     */
    protected function deleteFromDatabase(AbstractEntity $item): void
    {
        $em = $this->getManager();
        $em->remove($item);
        $em->flush();
    }

    /**
     * Edit an entity.
     *
     * @param request        $request    the request
     * @param AbstractEntity $item       the entity to edit
     * @param array          $parameters the edit parameters. The following keys may be added:
     *                                   <ul>
     *                                   <li><code>success</code> : the message to display on success (optional).</li>
     *                                   <li><code>route</code> : the route to display on success (optional).</li>
     *                                   </ul>
     * @psalm-param T $item
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        // check permission
        $isNew = $item->isNew();
        $attribute = $isNew ? EntityVoterInterface::ATTRIBUTE_ADD : EntityVoterInterface::ATTRIBUTE_EDIT;
        $this->checkPermission($attribute);

        // form
        $type = $this->getEditFormType();
        $form = $this->createForm($type, $item);
        if ($this->handleRequestForm($request, $form)) {
            // save
            $this->saveToDatabase($item);

            // message
            /** @psalm-var string $key */
            $key = $isNew ? $parameters['success'] ?? 'common.add_success' : $parameters['success'] ?? 'common.edit_success';
            $message = $this->trans($key, ['%name%' => $item->getDisplay()]);
            $this->succes($message);

            // redirect
            $id = $item->getId();
            $route = (string) ($parameters['route'] ?? $this->getDefaultRoute());

            return $this->getUrlGenerator()->redirect($request, $id, $route);
        }

        // remove unused parameters
        unset($parameters['success'], $parameters['route']);

        // update parameters
        $parameters['new'] = $isNew;
        $parameters['item'] = $item;
        $parameters['form'] = $form;
        $this->updateQueryParameters($request, $parameters, (int) $item->getId());

        // show form
        return $this->renderForm($this->getEditTemplate(), $parameters);
    }

    /**
     * Gets the Twig template (path) name used to display entities as card.
     */
    protected function getCardTemplate(): string
    {
        return \sprintf('%1$s/%1$s_card.html.twig', $this->lowerName);
    }

    /**
     * Gets the default route name used to display the list of entities.
     */
    protected function getDefaultRoute(): string
    {
        if ($this->isDisplayTabular()) {
            return \sprintf('%s_table', $this->lowerName);
        }

        return \sprintf('%s_list', $this->lowerName);
    }

    /**
     * Gets sorted distinct and not null values of the given column.
     *
     * @param string $field  the column name to get values for
     * @param string $search a value to search within the column
     * @param int    $limit  the maximum number of results to retrieve or -1 for all
     */
    protected function getDistinctValues(string $field, ?string $search = null, int $limit = -1): array
    {
        return $this->repository->getDistinctValues($field, $search, $limit);
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
     * @param string $field     the optional sorted field
     * @param string $mode      the optional sort mode ("ASC" or "DESC")
     * @param array  $criterias the filter criterias
     * @param string $alias     the entity alias
     *
     * @return AbstractEntity[] the entities
     *
     * @psalm-param array<Criteria|string> $criterias
     * @psalm-return T[]
     * @psalm-suppress MixedReturnTypeCoercion
     */
    protected function getEntities(?string $field = null, string $mode = Criteria::ASC, array $criterias = [], string $alias = AbstractRepository::DEFAULT_ALIAS): array
    {
        $sortedFields = null !== $field ? [$field => $mode] : [];

        return $this->repository
            ->getSearchQuery($sortedFields, $criterias, $alias)
            ->getResult();
    }

    /**
     * Gets the entity manager.
     */
    protected function getManager(): EntityManagerInterface
    {
        return $this->repository->getEntityManager();
    }

    /**
     * Gets the Twig template (path) name used to show an entity.
     */
    protected function getShowTemplate(): string
    {
        return \sprintf('%1$s/%1$s_show.html.twig', $this->lowerName);
    }

    /**
     * Gets the Twig template (path) name used to display entities as table.
     */
    protected function getTableTemplate(): string
    {
        return \sprintf('%1$s/%1$s_table.html.twig', $this->lowerName);
    }

    /**
     * Render the entities as card.
     *
     * @param Request $request    the request
     * @param string  $sortField  the default sorted field
     * @param string  $sortMode   the default sorted direction
     * @param array   $sortFields the allowed sorted fields
     * @param array   $parameters the parameters to pass to the template
     *
     * @return Response the rendered template
     */
    protected function renderCard(Request $request, string $sortField, string $sortMode = Criteria::ASC, array $sortFields = [], array $parameters = []): Response
    {
        // check permission
        $this->checkPermission(EntityVoterInterface::ATTRIBUTE_LIST);

        // keys
        $key = Utils::getShortName($this->className);
        $keyField = $key . '.sortField';
        $keySort = $key . '.sortMode';

        // get session values
        $field = $this->getSessionString($keyField, $sortField);
        $mode = $this->getSessionString($keySort, $sortMode);

        // get request values
        $id = $this->getRequestInt($request, 'id', 0);
        $query = $this->getRequestString($request, 'query', '');
        $mode = $this->getRequestString($request, 'sortMode', $mode);
        $field = $this->getRequestString($request, 'sortField', $field);

        // update session values
        if ($sortField === $field && $sortMode === $mode) {
            $this->removeSessionValues([$keyField, $keySort]);
        } else {
            $this->setSessionValues([
                $keyField => $field,
                $keySort => $mode,
            ]);
        }

        // get items
        /** @psalm-var string|null $field */
        /** @psalm-var string $mode */
        $items = $this->getEntities($field, $mode);

        // parameters
        $parameters = \array_merge([
            'items' => $items,
            'id' => $id,
            'query' => $query,
            'sortMode' => $mode,
            'sortField' => $field,
            'sortFields' => $sortFields,
        ], $parameters);

        return $this->renderForm($this->getCardTemplate(), $parameters);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException if the access is denied
     */
    protected function renderPdfDocument(PdfDocument $doc, bool $inline = true, string $name = ''): PdfResponse
    {
        $this->checkPermission(EntityVoterInterface::ATTRIBUTE_EXPORT);

        return parent::renderPdfDocument($doc, $inline, $name);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException if the access is denied
     */
    protected function renderSpreadsheetDocument(SpreadsheetDocument $doc, bool $inline = true, string $name = ''): SpreadsheetResponse
    {
        $this->checkPermission(EntityVoterInterface::ATTRIBUTE_EXPORT);

        return parent::renderSpreadsheetDocument($doc, $inline, $name);
    }

    /**
     * Render the entities as data table.
     *
     * @param Request                 $request    the request to get parameters
     * @param AbstractEntityDataTable $table      the data table
     * @param array                   $attributes additional data table attributes
     * @param array                   $parameters parameters to pass to the view
     *
     * @return Response a JSON response if is a callback, the data table view otherwise
     *
     * @psalm-param AbstractEntityDataTable<T> $table
     */
    protected function renderTable(Request $request, AbstractEntityDataTable $table, array $attributes = [], array $parameters = []): Response
    {
        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        // check permission
        $this->checkPermission(EntityVoterInterface::ATTRIBUTE_LIST);

        // parameters
        $parameters += [
            'results' => $results,
            'attributes' => $attributes,
            'columns' => $table->getColumns(),
        ];

        return $this->renderForm($this->getTableTemplate(), $parameters);
    }

    /**
     * This function save the given entity to the database.
     *
     * Derived class can update entity before it is saved to the database.
     *
     * @param AbstractEntity $item the entity to save
     * @psalm-param T $item
     */
    protected function saveToDatabase(AbstractEntity $item): void
    {
        $em = $this->getManager();
        if ($item->isNew()) {
            $em->persist($item);
        }
        $em->flush();
    }

    /**
     * Show properties of an entity.
     *
     * @param AbstractEntity $item       the entity to show
     * @param array          $parameters the additional parameters to pass to the template
     *
     * @throws \Symfony\Component\Finder\Exception\AccessDeniedException if the access is denied
     * @psalm-param T $item
     */
    protected function showEntity(AbstractEntity $item, array $parameters = []): Response
    {
        // check permission
        $this->checkPermission(EntityVoterInterface::ATTRIBUTE_SHOW);

        // add item parameter
        $parameters['item'] = $item;

        // render
        return $this->renderForm($this->getShowTemplate(), $parameters);
    }

    /**
     * Update the parameters by adding the request query values.
     *
     * @param Request $request    the request to get the query values
     * @param array   $parameters the parameters to update
     * @param int     $id         an optional entity identifier
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
}
