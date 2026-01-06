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
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait to render a form exception.
 *
 * @phpstan-require-extends AbstractController
 */
trait FormExceptionTrait
{
    protected function renderFormException(string $id, \Throwable $e, LoggerInterface $logger): Response
    {
        $message = $this->trans($id);
        $context = $this->getExceptionContext($e);
        $logger->error($message, $context);

        return $this->render('bundles/TwigBundle/Exception/exception.html.twig', [
            'message' => $message,
            'exception' => $e,
        ]);
    }
}
