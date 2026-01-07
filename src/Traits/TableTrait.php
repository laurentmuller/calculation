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
            $this->saveCookie($response, $results, TableInterface::PARAM_VIEW, TableView::TABLE);
            $this->saveCookie($response, $results, TableInterface::PARAM_LIMIT, TableView::TABLE->getPageSize(), $prefix);
            $this->saveCookie($response, $results, TableInterface::PARAM_SORT, $query->sort, $prefix);
            $this->saveCookie($response, $results, TableInterface::PARAM_ORDER, $query->order, $prefix);

            $userParameters = $this->getUserParameters();
            $display = $userParameters->getDisplay();
            if ($display->getDisplayMode() !== $query->view) {
                $display->setDisplayMode($query->view);
                $userParameters->save();
            }

            return $response;
        } catch (\Throwable $e) {
            if ($query->callback) {
                $parameters = $this->logFormException('error_page.description', $e, $logger);
                $parameters['exception'] = $parameters['context'];
                unset($parameters['context']);

                return $this->jsonFalse($parameters, Response::HTTP_BAD_REQUEST);
            }

            $parameters = [
                'status_code' => Response::HTTP_BAD_REQUEST,
            ];

            return $this->renderFormException('errors.invalid_request', $e, $logger, $parameters);
        }
    }

    /**
     * Save the given parameter from the data result to a cookie.
     */
    protected function saveCookie(
        Response $response,
        DataResults $results,
        string $key,
        string|bool|int|\BackedEnum|null $default = null,
        string $prefix = ''
    ): void {
        $value = $results->getParameter($key, $default);
        $this->updateCookie($response, $key, $value, $prefix);
    }
}
