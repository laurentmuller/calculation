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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait to render JSON response.
 *
 * @phpstan-require-extends AbstractController
 */
trait JsonResponseTrait
{
    use ExceptionContextTrait;

    /**
     * Returns the given exception as a JsonResponse with false as the result.
     *
     * @param \Exception $e       the exception to serialize
     * @param ?string    $message the optional error message
     * @param int        $status  the HTTP status code
     */
    protected function jsonException(
        \Exception $e,
        ?string $message = null,
        int $status = Response::HTTP_OK
    ): JsonResponse {
        return $this->jsonFalse([
            'message' => $message ?? $e->getMessage(),
            'exception' => $this->getExceptionContext($e),
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
        return $this->json(\array_merge_recursive(['result' => false], $data), $status);
    }

    /**
     * Returns a JSON response with true as the result.
     *
     * @param array $data   the data to merge within the response
     * @param int   $status the HTTP status code
     */
    protected function jsonTrue(array $data = [], int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json(\array_merge_recursive(['result' => true], $data), $status);
    }
}
