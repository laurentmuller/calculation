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

namespace Twig;

use App\Tests\Twig\RuntimeTestCase;
use App\Twig\HtmlDataExtension;

/**
 * @extends RuntimeTestCase<HtmlDataExtension>
 */
final class HtmlDataExtensionTest extends RuntimeTestCase
{
    #[\Override]
    protected function createService(): HtmlDataExtension
    {
        return new HtmlDataExtension();
    }

    #[\Override]
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/HtmlDataExtension';
    }
}
