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

use App\Twig\EnumExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Twig\Test\IntegrationTestCase;

#[CoversClass(EnumExtension::class)]
class EnumExtensionTest extends IntegrationTestCase
{
    protected function getExtensions(): array
    {
        return [new EnumExtension()];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/EnumExtension';
    }
}