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

use App\Twig\NonceExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(NonceExtension::class)]
class NonceExtensionTest extends KernelTestCase
{
    private ?NonceExtension $extension = null;

    /**
     * @throws \Exception
     *
     * @psalm-suppress RedundantCondition
     */
    protected function setUp(): void
    {
        $extension = self::getContainer()->get(NonceExtension::class);
        if ($extension instanceof NonceExtension) {
            $this->extension = $extension;
        }
    }

    public function testExtensionNotNull(): void
    {
        self::assertNotNull($this->extension);
    }

    /**
     * @throws \Exception
     */
    public function testLength32(): void
    {
        self::assertNotNull($this->extension);
        $nonce = $this->extension->getNonce(32);
        self::assertSame(64, \strlen($nonce));
    }

    /**
     * @throws \Exception
     */
    public function testLengthDefault(): void
    {
        self::assertNotNull($this->extension);
        $nonce = $this->extension->getNonce();
        self::assertSame(32, \strlen($nonce));
    }
}
