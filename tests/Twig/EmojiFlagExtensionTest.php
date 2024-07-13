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

namespace App\Tests\Twig;

use App\Service\CountryFlagService;
use App\Twig\EmojiFlagExtension;

class EmojiFlagExtensionTest extends IntegrationTestCase
{
    protected function getExtensions(): array
    {
        $service = new CountryFlagService();

        return [new EmojiFlagExtension($service)];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/EmojiFlagExtension';
    }
}
