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
use PHPUnit\Framework\Attributes\CoversClass;
use Twig\Error\Error;
use Twig\Test\IntegrationTestCase;

#[CoversClass(FormatExtension::class)]
class FormatExtensionTest extends IntegrationTestCase
{
    use TranslatorMockTrait;

    /**
     * @psalm-suppress MissingParamType
     *
     * @throws Error
     */
    protected function doIntegrationTest(// @phpstan-ignore-line
        $file,
        $message,
        $condition,
        $templates,
        $exception,
        $outputs,
        $deprecation = ''
    ): void {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        parent::doIntegrationTest($file, $message, $condition, $templates, $exception, $outputs, $deprecation);
    }

    protected function getExtensions(): array
    {
        return [new FormatExtension($this->createMockTranslator())];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/FormatExtension';
    }
}
