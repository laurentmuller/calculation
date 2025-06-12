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
use Twig\Extension\AttributeExtension;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class HighlightExtensionTest extends IntegrationTestCase implements RuntimeLoaderInterface
{
    private HighlightExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new HighlightExtension();
    }

    #[\Override]
    public function load(string $class): ?object
    {
        if (HighlightExtension::class === $class) {
            return $this->extension;
        }

        return null;
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [new AttributeExtension(HighlightExtension::class)];
    }

    #[\Override]
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/HighlightExtension';
    }

    #[\Override]
    protected function getRuntimeLoaders(): array
    {
        return [$this];
    }
}
