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

use App\Tests\KernelServiceTestCase;
use App\Twig\NonceExtension;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NonceExtension::class)]
class NonceExtensionTest extends KernelServiceTestCase
{
    private NonceExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = $this->getService(NonceExtension::class);
    }

    /**
     * @throws \Exception
     */
    public function testFunctions(): void
    {
        $functions = $this->extension->getFunctions();
        self::assertCount(1, $functions);
        $function = $functions[0];
        self::assertSame('csp_nonce', $function
            ->getName());
    }

    /**
     * @throws \Exception
     */
    public function testLength32(): void
    {
        $nonce = $this->extension->getNonce(32);
        self::assertSame(64, \strlen($nonce));
    }

    /**
     * @throws \Exception
     */
    public function testLengthDefault(): void
    {
        $nonce = $this->extension->getNonce();
        self::assertSame(32, \strlen($nonce));
    }
}
