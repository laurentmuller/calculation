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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Unit test for Twig {@link ConstantExtension}.
 */
class ConstantExtensionTest extends KernelTestCase
{
    private ?ConstantExtension $extension;

    /**
     * {@inheritDoc}
     *
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $extension = self::getContainer()->get(ConstantExtension::class);
        if ($extension instanceof ConstantExtension) {
            $this->extension = $extension;
        }
    }

    public function getCalculationServiceConstants(): array
    {
        return [
            ['ROW_EMPTY', 0],
            ['ROW_GROUP', 1],
            ['ROW_TOTAL_GROUP', 2],
            ['ROW_GLOBAL_MARGIN', 3],
            ['ROW_TOTAL_NET', 4],
            ['ROW_USER_MARGIN', 5],
            ['ROW_OVERALL_TOTAL', 6],
        ];
    }

    public function getEntityVoterConstants(): array
    {
        return [
            ['ATTRIBUTE_ADD', 'ADD'],
            ['ATTRIBUTE_DELETE', 'DELETE'],
            ['ATTRIBUTE_EDIT', 'EDIT'],
            ['ATTRIBUTE_EXPORT', 'EXPORT'],
            ['ATTRIBUTE_LIST', 'LIST'],
            ['ATTRIBUTE_SHOW', 'SHOW'],

            ['ENTITY_CALCULATION', 'EntityCalculation'],
            ['ENTITY_CALCULATION_STATE', 'EntityCalculationState'],
            ['ENTITY_CATEGORY', 'EntityCategory'],
            ['ENTITY_CUSTOMER', 'EntityCustomer'],
            ['ENTITY_GLOBAL_MARGIN', 'EntityGlobalMargin'],
            ['ENTITY_GROUP', 'EntityGroup'],
            ['ENTITY_LOG', 'EntityLog'],
            ['ENTITY_PRODUCT', 'EntityProduct'],
            ['ENTITY_TASK', 'EntityTask'],
            ['ENTITY_USER', 'EntityUser'],
        ];
    }

    /**
     * @dataProvider getCalculationServiceConstants
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testCalculationService(string $key, int $value): void
    {
        $globals = $this->extension->getGlobals();
        self::assertArrayHasKey($key, $globals);
        self::assertIsInt($globals[$key]);
        self::assertEquals($value, $globals[$key]);
    }

    /**
     * @dataProvider getEntityVoterConstants
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testEntityVoter(string $key, string $value): void
    {
        $globals = $this->extension->getGlobals();
        self::assertArrayHasKey($key, $globals);
        self::assertIsString($globals[$key]);
        self::assertEquals($value, $globals[$key]);
    }

    public function testExtensionNotNull(): void
    {
        self::assertNotNull($this->extension);
    }
}
