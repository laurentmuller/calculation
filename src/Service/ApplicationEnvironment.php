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

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service to get application environment.
 */
readonly class ApplicationEnvironment extends AbstractEnvironmentService
{
    /**
     * @throws \ValueError If there is no matching case defined
     */
    public function __construct(#[Autowire('%app_mode%')] string $value)
    {
        parent::__construct($value);
    }
}
