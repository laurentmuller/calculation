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

use App\Twig\HighlightExtension;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HighlightExtension::class)]
class HighlightExtensionTest extends IntegrationTestCase
{
    protected function getExtensions(): array
    {
        return [new HighlightExtension()];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/HighlightExtension';
    }
}
