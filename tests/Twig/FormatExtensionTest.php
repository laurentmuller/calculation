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
use App\Twig\FormatExtension;
use App\Utils\FormatUtils;
use Twig\Error\Error;
use Twig\Extension\AttributeExtension;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class FormatExtensionTest extends IntegrationTestCase implements RuntimeLoaderInterface
{
    use TranslatorMockTrait;

    private FormatExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new FormatExtension($this->createMockTranslator());
    }

    #[\Override]
    public function load(string $class): ?object
    {
        if (FormatExtension::class === $class) {
            return $this->extension;
        }

        return null;
    }

    /**
     * @throws Error
     */
    #[\Override]
    protected function doIntegrationTest(
        string $file,
        string $message,
        string $condition,
        array $templates,
        false|string $exception,
        array $outputs,
        string $deprecation
    ): void {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        parent::doIntegrationTest($file, $message, $condition, $templates, $exception, $outputs, $deprecation);
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new AttributeExtension(FormatExtension::class),
            new AttributeExtension(FormatUtils::class),
        ];
    }

    #[\Override]
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/FormatExtension';
    }

    #[\Override]
    protected function getRuntimeLoaders(): array
    {
        return [$this];
    }
}
