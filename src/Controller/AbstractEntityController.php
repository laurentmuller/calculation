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
use Exception;
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
    public function __construct(protected AbstractRepository $repository)
    {
        $this->className = $repository->getClassName();
        $this->lowerName = \strtolower(Utils::getShortName($this->className));
    }

    /**
     * Throws an exception unless the given attribute is granted against
     * the current authentication token and this entity class name.
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException if the access is denied
     */
    protected function checkPermission(EntityPermission $permission): void
    {
        $subject = EntityName::tryFindValue($this->className);
        $this->denyAccessUnlessGranted($permission, $subject);
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
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \ReflectionException
     */
    protected function deleteEntity(Request $request, AbstractEntity $item, LoggerInterface $logger, array $parameters = []): Response
    {
        // check permission
        $this->checkPermission(EntityPermission::DELETE);

        // save display
        $display = $item->getDisplay();

        // add item as parameter
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
        $this->repository->remove($item);
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
            /** @psalm-var string $key */
            $key = $isNew ? $parameters['success'] ?? 'common.add_success' : $parameters['success'] ?? 'common.edit_success';
            $message = $this->trans($key, ['%name%' => $item->getDisplay()]);
            $this->success($message);

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
     * @psalm-return T[]
     *
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-param literal-string $alias
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
     * @psalm-param T $item
     */
    protected function showEntity(AbstractEntity $item, array $parameters = []): Response
    {
        // check permission
        $this->checkPermission(EntityPermission::SHOW);

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
}
