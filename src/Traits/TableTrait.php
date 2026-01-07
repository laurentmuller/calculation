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

namespace App\Traits;

use App\Controller\AbstractController;
use App\Enums\EntityPermission;
use App\Enums\FlashType;
use App\Enums\TableView;
use App\Interfaces\TableInterface;
use App\Model\TranslatableFlashMessage;
use App\Table\AbstractTable;
use App\Table\DataQuery;
use App\Table\DataResults;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait to handle a table request.
 *
 * @phpstan-require-extends AbstractController
 */
trait TableTrait
{
    use CookieTrait;
    use FormExceptionTrait;

    /**
     * Handles a table request.
     */
    protected function handleTableRequest(
        AbstractTable $table,
        LoggerInterface $logger,
        DataQuery $query,
        string $template
    ): Response {
        // check granted
        $subject = $table->getEntityClassName();
        if (null !== $subject) {
            $this->denyAccessUnlessGranted(EntityPermission::LIST, $subject);
        }

        // check empty
        $message = $table->getEmptyMessage();
        if (null !== $message) {
            return $this->redirectToHomePage(
                message: new TranslatableFlashMessage(
                    message: $message,
                    type: FlashType::INFO,
                )
            );
        }

        try {
            $prefix = $query->prefix;
            $results = $table->processDataQuery($query);
            $response = $query->callback ? $this->json($results) : $this->render($template, (array) $results);
            $this->saveTableCookie($response, $results, TableInterface::PARAM_VIEW, TableView::TABLE);
            $this->saveTableCookie($response, $results, TableInterface::PARAM_LIMIT, TableView::TABLE->getPageSize(), $prefix);
            $this->saveTableCookie($response, $results, TableInterface::PARAM_SORT, $query->sort, $prefix);
            $this->saveTableCookie($response, $results, TableInterface::PARAM_ORDER, $query->order, $prefix);
            $this->saveTableView($query->view);

            return $response;
        } catch (\Throwable $e) {
            return $this->handleTableException($query->callback, $logger, $e);
        }
    }

    /**
     * Handle table exception.
     */
    private function handleTableException(bool $callback, LoggerInterface $logger, \Throwable $e): Response
    {
        if ($callback) {
            $parameters = $this->logFormException('error_page.description', $e, $logger);
            $parameters = [
                'message' => $parameters['message'],
                'exception' => $parameters['context'],
            ];

            return $this->jsonFalse($parameters, Response::HTTP_BAD_REQUEST);
        }

        $parameters = [
            'status_code' => Response::HTTP_BAD_REQUEST,
        ];

        return $this->renderFormException('errors.invalid_request', $e, $logger, $parameters);
    }

    /**
     * Save the table parameter from the data result to a cookie.
     */
    private function saveTableCookie(
        Response $response,
        DataResults $results,
        string $key,
        string|bool|int|\BackedEnum|null $default = null,
        string $prefix = ''
    ): void {
        $value = $results->getParameter($key, $default);
        $this->updateCookie($response, $key, $value, $prefix);
    }

    /**
     * Save the display mode.
     */
    private function saveTableView(TableView $view): void
    {
        $userParameters = $this->getUserParameters();
        $display = $userParameters->getDisplay();
        if ($display->getDisplayMode() !== $view) {
            $display->setDisplayMode($view);
            $userParameters->save();
        }
    }
}
