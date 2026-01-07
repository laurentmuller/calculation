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

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait to log and render an exception.
 */
trait FormExceptionTrait
{
    /**
     * @return array{message: string, context: array<string, string|int>, exception: \Throwable}
     */
    protected function logFormException(string $id, \Throwable $e, LoggerInterface $logger): array
    {
        $message = $this->trans($id);
        $context = $this->getExceptionContext($e);
        $logger->error($message, $context);

        return [
            'message' => $message,
            'context' => $context,
            'exception' => $e,
        ];
    }

    protected function renderFormException(string $id, \Throwable $e, LoggerInterface $logger, array $parameters = []): Response
    {
        $parameters = \array_merge(
            $this->logFormException($id, $e, $logger),
            $parameters
        );

        return $this->render('bundles/TwigBundle/Exception/exception.html.twig', $parameters);
    }
}
