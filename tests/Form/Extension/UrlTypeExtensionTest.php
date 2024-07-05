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

namespace App\Tests\Form\Extension;

use App\Form\Extension\UrlTypeExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(UrlTypeExtension::class)]
class UrlTypeExtensionTest extends TypeTestCase
{
    public function testFormViewWithDefaultProtocol(): void
    {
        $options = ['default_protocol' => 'http'];
        $view = $this->factory->create(UrlType::class, null, $options)
            ->createView();
        self::assertArrayHasKey('attr', $view->vars);
        $attr = $view->vars['attr'];
        self::assertArrayHasKey('data-protocol', $attr);
        self::assertSame('http', $attr['data-protocol']);
    }

    public function testFormViewWithFtpProtocol(): void
    {
        $options = ['default_protocol' => 'ftp'];
        $view = $this->factory->create(UrlType::class, null, $options)
            ->createView();
        self::assertArrayHasKey('attr', $view->vars);
        $attr = $view->vars['attr'];
        self::assertArrayHasKey('data-protocol', $attr);
        self::assertSame('ftp', $attr['data-protocol']);
    }

    protected function getTypeExtensions(): array
    {
        /** @psalm-var array $extensions */
        $extensions = parent::getTypeExtensions();
        $extensions[] = new UrlTypeExtension();

        return $extensions;
    }
}
