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
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract service to detect environment variable.
 */
abstract readonly class AbstractEnvironmentService implements TranslatableInterface
{
    private Environment $environment;

    /**
     * @throws \ValueError If the $environment parameter is a string and there is no matching case defined
     */
    public function __construct(Environment|string $environment)
    {
        $this->environment = $environment instanceof Environment ? $environment : Environment::from($environment);
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

    #[\Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->environment->trans($translator, $locale);
    }
}
