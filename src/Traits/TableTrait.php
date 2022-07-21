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
use App\Enums\TableView;
use App\Interfaces\TableInterface;
use App\Table\AbstractTable;
use App\Table\DataResults;
use App\Util\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait to handle an abstract table.
 */
trait TableTrait
{
    use CookieTrait;

    /**
     * Handles a table request.
     *
     * @throws \ReflectionException
     */
    protected function handleTableRequest(Request $request, AbstractTable $table, string $template): Response
    {
        // check permission
        if ($subject = $table->getEntityClassName()) {
            $this->denyAccessUnlessGranted(EntityPermission::LIST, $subject);
        }

        try {
            // update request parameters
            $view = $this->updateRequest($request, TableInterface::PARAM_VIEW, TableView::TABLE->value);
            if (\is_string($view)) {
                $enum = TableView::tryFrom($view) ?? TableView::TABLE;
                $this->updateRequest($request, TableInterface::PARAM_LIMIT, $enum->getPageSize(), $enum->value);
            }

            // check empty
            if ($message = $table->checkEmpty()) {
                $this->infoTrans($message);

                return $this->redirectToHomePage();
            }

            // get query and results
            $query = $table->getDataQuery($request);
            $results = $table->processQuery($query);

            // callback?
            if ($query->callback) {
                $response = $this->json($results);
            } else {
                // empty?
                if (0 === $results->totalNotFiltered && !$table->isEmptyAllowed()) {
                    $this->infoTrans($table->getEmptyMessage());

                    return $this->redirectToHomePage();
                }

                // generate
                $response = $this->renderForm($template, (array) $results);
            }

            // save results
            $this->saveCookie($response, $results, TableInterface::PARAM_VIEW, TableView::TABLE->value);
            $this->saveCookie($response, $results, TableInterface::PARAM_LIMIT, TableView::TABLE->getPageSize(), $query->view->value);

            return $response;
        } catch (\Throwable $e) {
            $status = Response::HTTP_BAD_REQUEST;
            $parameters = [
                'result' => false,
                'status_code' => $status,
                'status_text' => $this->trans('errors.invalid_request'),
                'message' => $this->trans('error_page.description'),
                'exception' => Utils::getExceptionContext($e),
            ];

            if ($request->isXmlHttpRequest()) {
                return $this->json($parameters, $status);
            }

            return $this->renderForm('bundles/TwigBundle/Exception/error.html.twig', $parameters);
        }
    }

    /**
     * Save the given parameter from the data result to a cookie.
     */
    protected function saveCookie(Response $response, DataResults $results, string $key, string|int|float|bool|null $default = null, string $prefix = ''): void
    {
        /** @psalm-var string|int|float|bool|array|null $value */
        $value = $results->getParams($key, $default);
        $path = $this->getStringParameter('cookie_path');
        $this->updateCookie($response, $key, $value, $prefix, $path);
    }

    /**
     * @param string|int|float|bool|null $default the default value if the input key does not exist
     *
     * @return string|int|float|bool|null the request value, the cookie value or the default value
     * @psalm-suppress InvalidScalarArgument
     */
    private function updateRequest(Request $request, string $key, string|int|float|bool|null $default = null, string $prefix = ''): string|int|float|bool|null
    {
        $input = Utils::getRequestInputBag($request);
        $value = $input->get($key);
        if (null === $value) {
            $name = $this->getCookieName($key, $prefix);
            $value = $request->cookies->get($name, $default); // @phpstan-ignore-line
            if (null !== $value) {
                $input->set($key, $value);
            }
        }

        return $value;
    }
}
