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

namespace App\Service;

use App\Enums\Environment;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service to detect environnement variable.
 */
readonly class EnvironmentService
{
    private Environment $environment;

    /**
     * @throws \ValueError If there is no matching case defined
     */
    public function __construct(
        #[Autowire('%kernel.environment%')]
        string $environment
    ) {
        $this->environment = Environment::from($environment);
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function isDevelopment(): bool
    {
        return $this->environment->isDevelopment();
    }

    public function isProduction(): bool
    {
        return $this->environment->isProduction();
    }

    public function isTest(): bool
    {
        return $this->environment->isTest();
    }
}
