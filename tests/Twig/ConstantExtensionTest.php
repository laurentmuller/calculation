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

use App\Twig\ConstantExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(ConstantExtension::class)]
class ConstantExtensionTest extends KernelTestCase
{
    private ?ConstantExtension $extension = null;

    /**
     * @throws \Exception
     *
     * @psalm-suppress RedundantCondition
     */
    protected function setUp(): void
    {
        $extension = self::getContainer()->get(ConstantExtension::class);
        if ($extension instanceof ConstantExtension) {
            $this->extension = $extension;
        }
    }

    public static function getCalculationServiceConstants(): \Iterator
    {
        yield ['ROW_EMPTY', 0];
        yield ['ROW_GROUP', 1];
        yield ['ROW_TOTAL_GROUP', 2];
        yield ['ROW_GLOBAL_MARGIN', 3];
        yield ['ROW_TOTAL_NET', 4];
        yield ['ROW_USER_MARGIN', 5];
        yield ['ROW_OVERALL_TOTAL', 6];
    }

    public static function getEntityVoterConstants(): \Iterator
    {
        yield ['PERMISSION_ADD', 'ADD'];
        yield ['PERMISSION_DELETE', 'DELETE'];
        yield ['PERMISSION_EDIT', 'EDIT'];
        yield ['PERMISSION_EXPORT', 'EXPORT'];
        yield ['PERMISSION_LIST', 'LIST'];
        yield ['PERMISSION_SHOW', 'SHOW'];
        yield ['ENTITY_CALCULATION', 'EntityCalculation'];
        yield ['ENTITY_CALCULATION_STATE', 'EntityCalculationState'];
        yield ['ENTITY_CATEGORY', 'EntityCategory'];
        yield ['ENTITY_CUSTOMER', 'EntityCustomer'];
        yield ['ENTITY_GLOBAL_MARGIN', 'EntityGlobalMargin'];
        yield ['ENTITY_GROUP', 'EntityGroup'];
        yield ['ENTITY_LOG', 'EntityLog'];
        yield ['ENTITY_PRODUCT', 'EntityProduct'];
        yield ['ENTITY_TASK', 'EntityTask'];
        yield ['ENTITY_USER', 'EntityUser'];
    }

    /**
     * @throws InvalidArgumentException
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getCalculationServiceConstants')]
    public function testCalculationService(string $key, int $value): void
    {
        self::assertNotNull($this->extension);
        $globals = $this->extension->getGlobals();
        self::assertArrayHasKey($key, $globals);
        self::assertIsInt($globals[$key]);
        self::assertSame($value, $globals[$key]);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getEntityVoterConstants')]
    public function testEntityVoter(string $key, string $value): void
    {
        self::assertNotNull($this->extension);
        $globals = $this->extension->getGlobals();
        self::assertArrayHasKey($key, $globals);
        self::assertIsString($globals[$key]);
        self::assertSame($value, $globals[$key]);
    }

    public function testExtensionNotNull(): void
    {
        self::assertNotNull($this->extension);
    }
}
