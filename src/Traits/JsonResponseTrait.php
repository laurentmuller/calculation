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
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait to render JSON response.
 */
trait JsonResponseTrait
{
    use ExceptionContextTrait;

    /**
     * Returns the given exception as a JsonResponse with false as the result.
     *
     * @param \Exception       $exception the exception to serialize
     * @param ?string          $message   the optional error message
     * @param int              $status    the HTTP status code
     * @param ?LoggerInterface $logger    if present, log the given exception
     */
    protected function jsonException(
        \Exception $exception,
        ?string $message = null,
        int $status = Response::HTTP_OK,
        ?LoggerInterface $logger = null
    ): JsonResponse {
        $message ??= $exception->getMessage();
        $context = $this->getExceptionContext($exception);
        $logger?->error($message, $context);

        return $this->jsonFalse([
            'message' => $message,
            'exception' => $context,
        ], $status);
    }

    /**
     * Returns a JSON response with false as the result.
     *
     * @param array $data   the data to merge within the response
     * @param int   $status the HTTP status code
     */
    protected function jsonFalse(array $data = [], int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->createJsonResponse(false, $data, $status);
    }

    /**
     * Returns a JSON response with true as the result.
     *
     * @param array $data   the data to merge within the response
     * @param int   $status the HTTP status code
     */
    protected function jsonTrue(array $data = [], int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->createJsonResponse(true, $data, $status);
    }

    /**
     * Create a JSON response.
     *
     * @param bool  $result the desired result
     * @param array $data   the data to merge within the response
     * @param int   $status the HTTP status code
     */
    private function createJsonResponse(bool $result, array $data = [], int $status = Response::HTTP_OK): JsonResponse
    {
        $data = \array_merge_recursive(['result' => $result], $data);
        if ($this instanceof AbstractController) {
            return $this->json($data, $status);
        }

        return new JsonResponse($data, $status);
    }
}
