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

use App\Enums\EntityPermission;
use App\Enums\FlashType;
use App\Enums\TableView;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\TableInterface;
use App\Table\AbstractTable;
use App\Table\DataResults;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait to handle an <code></code>AbstractTable</code> request.
 */
trait TableTrait
{
    use CookieTrait;

    /**
     * Handles a table request.
     */
    protected function handleTableRequest(Request $request, AbstractTable $table, LoggerInterface $logger, string $template): Response
    {
        $subject = $table->getEntityClassName();
        if (null !== $subject) {
            $this->denyAccessUnlessGranted(EntityPermission::LIST, $subject);
        }
        $message = $table->getEmptyMessage();
        if (null !== $message) {
            return $this->redirectToHomePage(message: $message, type: FlashType::INFO);
        }

        try {
            $query = $table->getDataQuery($request);
            $results = $table->processDataQuery($query);
            $response = $query->callback ? $this->json($results) : $this->render($template, (array) $results);
            $this->saveCookie($response, $results, TableInterface::PARAM_VIEW, TableView::TABLE);
            $this->saveCookie($response, $results, TableInterface::PARAM_LIMIT, TableView::TABLE->getPageSize(), $table->getPrefix());
            $this->getUserService()->setProperty(PropertyServiceInterface::P_DISPLAY_MODE, $query->view);

            return $response;
        } catch (\Throwable $e) {
            $context = $this->getExceptionContext($e);
            $message = $this->trans('error_page.description');
            $logger->error($message, $context);
            $status = Response::HTTP_BAD_REQUEST;
            $parameters = [
                'result' => false,
                'message' => $message,
                'exception' => $context,
                'status_code' => $status,
                'status_text' => $this->trans('errors.invalid_request'),
            ];
            if ($request->isXmlHttpRequest()) {
                return $this->json($parameters, $status);
            }

            return $this->render('bundles/TwigBundle/Exception/error.html.twig', $parameters);
        }
    }

    /**
     * Save the given parameter from the data result to a cookie.
     */
    protected function saveCookie(Response $response, DataResults $results, string $key, string|bool|int|\BackedEnum $default = null, string $prefix = ''): void
    {
        $path = $this->getCookiePath();
        $value = $results->getParameter($key, $default);
        $this->updateCookie($response, $key, $value, $prefix, $path);
    }
}
