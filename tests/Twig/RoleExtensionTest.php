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

    public function testGetTranslator(): void
    {
        $extension = new RoleExtension($this->createMockTranslator());
        self::assertNotNull($extension->getTranslator());
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [new RoleExtension($this->createMockTranslator())];
    }

    #[\Override]
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/RoleExtension';
    }
}
