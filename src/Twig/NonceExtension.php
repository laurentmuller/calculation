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

namespace App\Twig;

use App\Service\NonceService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension to generate CSP nonce key.
 */
final class NonceExtension extends AbstractExtension
{
    public function __construct(private readonly NonceService $service)
    {
    }

    /**
     * @throws \Exception
     */
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('csp_nonce', $this->getNonce(...)),
        ];
    }

    /**
     * Gets the random nonce parameter.
     *
     * @phpstan-param positive-int $length
     */
    public function getNonce(?int $length = null): string
    {
        return $this->service->getNonce($length);
    }
}
