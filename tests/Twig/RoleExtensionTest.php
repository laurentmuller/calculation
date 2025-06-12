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

use App\Tests\TranslatorMockTrait;
use App\Twig\RoleExtension;

class RoleExtensionTest extends IntegrationTestCase
{
    use TranslatorMockTrait;

    private RoleExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new RoleExtension($this->createMockTranslator());
    }

    public function testGetTranslator(): void
    {
        $translator = $this->extension->getTranslator();
        $actual = $translator->trans('about.title');
        self::assertSame('about.title', $actual);
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [$this->extension];
    }

    #[\Override]
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/RoleExtension';
    }
}
