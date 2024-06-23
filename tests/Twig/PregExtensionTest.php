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

use App\Twig\PregExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Twig\Test\IntegrationTestCase;

#[CoversClass(PregExtension::class)]
class PregExtensionTest extends IntegrationTestCase
{
    protected function getExtensions(): array
    {
        return [new PregExtension()];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/PregExtension';
    }
}
