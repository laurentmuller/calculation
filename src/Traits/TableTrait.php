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

namespace App\Traits;

use App\BootstrapTable\AbstractTable;
use App\Interfaces\EntityVoterInterface;
use App\Interfaces\TableInterface;
use App\Util\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait to handle an abstract table.
 *
 * @author Laurent Muller
 */
trait TableTrait
{
    use CookieTrait;

    /**
     * Handles a table request.
     */
    protected function handleTableRequest(Request $request, AbstractTable $table, string $template): Response
    {
        // check permission
        if ($subject = $table->getEntityClassName()) {
            $this->denyAccessUnlessGranted(EntityVoterInterface::ATTRIBUTE_LIST, $subject);
        }

        // update request parameters
        $view = $this->updateRequest($request, TableInterface::PARAM_VIEW, TableInterface::VIEW_TABLE);
        if (\is_string($view)) {
            $this->updateRequest($request, TableInterface::PARAM_LIMIT, AbstractTable::getDefaultPageSize($view), $view);
        }

        // check empty
        if ($emptyMessage = $table->checkEmpty()) {
            $this->infoTrans($emptyMessage);

            return $this->redirectToHomePage();
        }

        try {
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
            $this->saveCookie($response, $results, TableInterface::PARAM_VIEW, TableInterface::VIEW_TABLE);
            $this->saveCookie($response, $results, TableInterface::PARAM_LIMIT, TableInterface::PAGE_SIZE, $query->view);

            return $response;
        } catch (\Exception $e) {
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
     * @param string|int|float|bool|null $default the default value if the input key does not exist
     *
     * @return string|int|float|bool|null the request value, the cookie value or the default value
     * @psalm-suppress InvalidScalarArgument
     */
    private function updateRequest(Request $request, string $key, $default = null, string $prefix = '')
    {
        $input = Utils::getRequestInputBag($request);
        $value = $input->get($key);
        if (null === $value) {
            $cookies = $request->cookies;
            $name = $this->getCookieName($key, $prefix);
            // @phpstan-ignore-next-line
            $value = $cookies->get($name, $default);
            if (null !== $value) {
                $input->set($key, $value);
            }
        }

        return $value;
    }
}
