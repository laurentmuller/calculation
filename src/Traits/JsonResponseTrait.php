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
     */
    protected function jsonException(\Exception $e, ?string $message = null): JsonResponse
    {
        return $this->jsonFalse([
            'message' => $message ?? $e->getMessage(),
            'exception' => $this->getExceptionContext($e),
        ]);
    }

    /**
     * Returns a JSON response with false as the result.
     *
     * @param array $data the data to merge within the response
     */
    protected function jsonFalse(array $data = []): JsonResponse
    {
        return $this->json(\array_merge_recursive(['result' => false], $data));
    }

    /**
     * Returns a JSON response with true as the result.
     *
     * @param array $data the data to merge within the response
     */
    protected function jsonTrue(array $data = []): JsonResponse
    {
        return $this->json(\array_merge_recursive(['result' => true], $data));
    }
}
