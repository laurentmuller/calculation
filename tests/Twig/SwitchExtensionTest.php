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

use App\Twig\Node\SwitchNode;
use App\Twig\SwitchExtension;
use App\Twig\TokenParser\SwitchTokenParser;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SwitchExtension::class)]
#[CoversClass(SwitchTokenParser::class)]
#[CoversClass(SwitchNode::class)]
class SwitchExtensionTest extends IntegrationTestCase
{
    protected function getExtensions(): array
    {
        return [new SwitchExtension()];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/SwitchExtension';
    }
}
