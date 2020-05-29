<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Controller;

use App\DataTables\Tables\EntityDataTable;
use App\Entity\EntityInterface;
use App\Interfaces\EntityVoterInterface;
use App\Pdf\PdfDocument;
use App\Pdf\PdfResponse;
use App\Repository\BaseRepository;
use App\Utils\Utils;
use Doctrine\Common\Collections\Criteria;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Abstract controller for entites management.
 *
 * @author Laurent Muller
 */
abstract class EntityController extends BaseController
{
    /**
     * The entity class name.
     *
     * @var string
     */
    protected $className;

    /**
     * Constructor.
     *
     * @param string $className the entity class name
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * Count the number of entities.
     */
    public function count(): int
    {
        return $this->getRepository()->count([]);
    }

    /**
     * Raised after the given entity is deleted.
     *
     * @param EntityInterface $item the deleted entity
     */
    protected function afterDelete(EntityInterface $item): void
    {
    }

    /**
     * Raised before the given entity is deleted.
     *
     * @param EntityInterface $item the entity to delete
     */
    protected function beforeDelete(EntityInterface $item): void
    {
    }

    /**
     * Delete an entity.
     *
     * @param request $request    the request
     * @param array   $parameters the delete parameters. The following keys must be set:
     *                            <ul>
     *                            <li><code>item</code> : the item to delete.</li>
     *                            <li><code>page_list</code> : the route to redirect on success.</li>
     *                            </ul>
     */
    protected function deletItem(Request $request, array $parameters): Response
    {
        // check permission
        $this->denyAccessUnlessGranted(EntityVoterInterface::ATTRIBUTE_DELETE, $this->className);

        /** @var EntityInterface $item */
        $item = $parameters['item'];
        $display = $item->getDisplay();

        // create form and handle request
        $form = $this->createFormBuilder()->getForm();
        if ($this->handleFormRequest($form, $request)) {
            try {
                // remove
                $this->beforeDelete($item);
                $em = $this->getManager();
                $em->remove($item);
                $em->flush();
                $this->afterDelete($item);

                // message
                $message = Utils::getArrayValue($parameters, 'success', 'common.delete_success');
                $this->warningTrans($message, ['%name%' => $display]);
            } catch (Exception $e) {
                // show error
                $parameters['exception'] = $e;
                $failure = Utils::getArrayValue($parameters, 'failure', 'common.delete_failure');
                $parameters['failure'] = $this->trans($failure, ['%name%' => $display]);

                return $this->render('@Twig/Exception/exception.html.twig', $parameters);
            }

            // redirect
            $route = Utils::getArrayValue($parameters, 'page_list', IndexController::HOME_PAGE);

            return $this->getUrlGenerator()->redirect($request, 0, $route);
        }

        // update parameters
        $parameters['title'] = Utils::getArrayValue($parameters, 'title', 'common.delete_title');
        $message = Utils::getArrayValue($parameters, 'message', 'common.delete_message');
        $parameters['message'] = $this->trans($message, ['%name%' => $display]);
        $parameters['selection'] = $item->getId();
        $parameters['form'] = $form->createView();

        // show page
        return $this->render('cards/card_delete.html.twig', $parameters);
    }

    /**
     * Edit an entity.
     *
     * @param request $request    the request
     * @param array   $parameters the edit parameters. The following keys must be set:
     *                            <ul>
     *                            <li><code>item</code> : the item to edit.</li>
     *                            <li><code>type</code> : the form type.</li>
     *                            <li><code>template</code> : the Twig template to render.</li>
     *                            <li><code>route</code> : the route to redirect on success.</li>
     *                            </ul>
     */
    protected function editItem(Request $request, array $parameters): Response
    {
        /** @var \App\Entity\EntityInterface $item */
        $item = $parameters['item'];
        $isNew = $item->isNew();

        // check permission
        $attribute = $isNew ? EntityVoterInterface::ATTRIBUTE_ADD : EntityVoterInterface::ATTRIBUTE_EDIT;
        $this->denyAccessUnlessGranted($attribute, $item);

        // form
        $type = $parameters['type'];
        $form = $this->createForm($type, $item);
        if ($this->handleFormRequest($form, $request)) {
            // update
            if ($this->updateItem($item)) {
                // save
                $em = $this->getManager();
                if ($isNew) {
                    $em->persist($item);
                }
                $em->flush();
            }

            // message
            if ($isNew) {
                $message = Utils::getArrayValue($parameters, 'success', 'common.add_success');
            } else {
                $message = Utils::getArrayValue($parameters, 'success', 'common.edit_success');
            }
            if ($title = Utils::getArrayValue($parameters, 'title')) {
                $title = $this->trans($title);
                $message = $this->trans($message, ['%name%' => $item->getDisplay()]);
                $this->succes("{$title}|{$message}");
            } else {
                $this->succesTrans($message, ['%name%' => $item->getDisplay()]);
            }

            // redirect
            $id = $item->getId();
            $route = $parameters['route'];

            return $this->getUrlGenerator()->redirect($request, $id, $route);
        }

        // template
        $template = $parameters['template'];

        // remove unused parameters
        unset($parameters['type'],
            $parameters['template'],
            $parameters['success'],
            $parameters['route']);

        // update parameters
        $parameters['new'] = $isNew;
        $parameters['form'] = $form->createView();
        if (!$isNew) {
            $parameters['selection'] = (int) $item->getId();
        }

        // show form
        return $this->render($template, $parameters);
    }

    /**
     * Finds an entity by it's identifier.
     *
     * @param int $id the entity identifier
     *
     * @return EntityInterface The entity
     *
     * @throws NotFoundHttpException if the entity is not found
     */
    protected function find($id)
    {
        $item = $this->getRepository()->find($id);
        if (null === $item) {
            $message = $this->trans('errors.item_not_found', [
                '%class%' => $this->getTranslatedClassName(),
                '%id%' => '#' . $id,
            ]);

            throw $this->createNotFoundException($message);
        }

        return $item;
    }

    /**
     * Gets the default route name used to display the list of entities.
     */
    abstract protected function getDefaultRoute(): string;

    /**
     * Gets sorted distinct and not null values of the given column.
     *
     * @param string $field  the column name to get values for
     * @param string $search a value to search within the column
     * @param int    $limit  the maximum number of results to retrieve or -1 for all
     */
    protected function getDistinctValues(string $field, ?string $search = null, int $limit = -1): array
    {
        return $this->getRepository()->getDistinctValues($field, $search, $limit);
    }

    /**
     * Gets the entities to display.
     *
     * @param string $field the sorted field
     * @param string $mode  the sort mode ("ASC" or "DESC")
     *
     * @return array the entities
     */
    protected function getItems(?string $field = null, string $mode = Criteria::ASC): array
    {
        $sortedFields = $field ? [$field => $mode] : [];

        return $this->getRepository()
            ->getSearchQuery($sortedFields)
            ->getResult();
    }

    /**
     * Gets the repository for the given manager.
     * This function use the class name given at the constructor.
     *
     * @return \App\Repository\BaseRepository the repository
     */
    protected function getRepository(): BaseRepository
    {
        return $this->getManager()->getRepository($this->className);
    }

    /**
     * Gets the entity short class name.
     *
     * @return string the entity short class name
     */
    protected function getShortClassName(): string
    {
        $reflection = new \ReflectionClass($this->className);

        return $reflection->getShortName();
    }

    /**
     * Gets the translated class name.
     *
     * @return string the translated class name
     */
    protected function getTranslatedClassName(): ?string
    {
        $className = $this->getShortClassName();

        return $this->trans(\strtolower($className) . '.name');
    }

    /**
     * Render the entities as card.
     *
     * @param Request $request    the request
     * @param string  $template   the template to render
     * @param string  $sortField  the default sorted field
     * @param string  $sortMode   the default sorted direction
     * @param array   $sortFields the allowed sorted fields
     * @param array   $parameters an array of parameters to pass to the view
     *
     * @return Response the rendered template
     */
    protected function renderCard(Request $request, string $template, string $sortField, string $sortMode = Criteria::ASC, array $sortFields = [], array $parameters = []): Response
    {
        // check permission
        $this->denyAccessUnlessGranted(EntityVoterInterface::ATTRIBUTE_LIST, $this->className);

        // get session values
        $key = $this->getShortClassName();
        $field = $this->getSessionString($key . '.sortField', $sortField);
        $mode = $this->getSessionString($key . '.sortMode', $sortMode);

        // get request values
        $field = $request->get('sortField', $field);
        $mode = $request->get('sortMode', $mode);
        $selection = $request->get('selection', 0);
        $query = $request->get('query', '');

        // update session values
        if ($sortField === $field && $sortMode === $mode) {
            $this->removeSessionValue($key . '.sortField');
            $this->removeSessionValue($key . '.sortMode');
        } else {
            $this->setSessionValue($key . '.sortField', $field);
            $this->setSessionValue($key . '.sortMode', $mode);
        }

        // get items
        $items = $this->getItems($field, $mode);

        // default action
        $edit = $this->getApplication()->isEditAction();

        // parameters
        $parameters = \array_merge($parameters, [
            'items' => $items,
            'query' => $query,
            'selection' => $selection,
            'sortField' => $field,
            'sortMode' => $mode,
            'sortFields' => $sortFields,
            'edit' => $edit,
        ]);

        return $this->render($template, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function renderDocument(PdfDocument $doc, bool $inline = true, string $name = '', bool $isUTF8 = false): PdfResponse
    {
        // check permission
        $this->denyAccessUnlessGranted(EntityVoterInterface::ATTRIBUTE_PDF, $this->className);

        return parent::renderDocument($doc, $inline, $name, $isUTF8);
    }

    /**
     * Show properties of an entity.
     *
     * @param string $template   the Twig template to render
     * @param mixed  $item       the entity to display
     * @param array  $parameters an array of parameters to pass to the view
     *
     * @throws \Symfony\Component\Finder\Exception\AccessDeniedException if the access is denied
     */
    protected function showItem(string $template, $item, array $parameters = []): Response
    {
        // check permission
        $this->denyAccessUnlessGranted(EntityVoterInterface::ATTRIBUTE_SHOW, $item);

        // add item parameter
        $parameters['item'] = $item;

        // render
        return $this->render($template, $parameters);
    }

    /**
     * Show the data table view.
     *
     * @param Request         $request    the request to get parameters
     * @param EntityDataTable $table      the datatable
     * @param string          $template   the template name to render
     * @param array           $attributes additional data table attributes
     *
     * @return Response a JSON response if a callback, the table view otherwise
     */
    protected function showTable(Request $request, EntityDataTable $table, string $template, array $attributes = []): Response
    {
        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        // update attributes
        $attributes['edit-action'] = \json_encode($this->getApplication()->isEditAction());

        // parameters
        $parameters = [
            'results' => $results,
            'columns' => $table->getColumns(),
            'attributes' => $attributes,
        ];

        return $this->render($template, $parameters);
    }

    /**
     * This function is called before an entity is saved to the database.
     *
     * Derived class can compute values and update entity.
     *
     * @param mixed $item the entity to be saved
     *
     * @return bool true if updated successfully; false to not save entity to the database
     */
    protected function updateItem($item): bool
    {
        return true;
    }
}
