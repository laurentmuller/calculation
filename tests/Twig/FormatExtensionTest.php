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

/**
 * @extends RuntimeTestCase<FormatExtension>
 */
final class FormatExtensionTest extends RuntimeTestCase
{
    use TranslatorMockTrait;

    public function testFormatBoolean(): void
    {
        $extension = $this->createService();
        $actual = $extension->formatBoolean(true);
        self::assertSame('common.value_true', $actual);
        $actual = $extension->formatBoolean(false);
        self::assertSame('common.value_false', $actual);
    }

    #[\Override]
    protected function createService(): FormatExtension
    {
        return new FormatExtension($this->createMockTranslator());
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
        return \array_merge(parent::getExtensions(), [new AttributeExtension(FormatUtils::class)]);
    }

    #[\Override]
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/FormatExtension';
    }
}
