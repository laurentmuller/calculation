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

namespace App\Tests\DataTransformer;

use App\Form\DataTransformer\AddressTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Mime\Address;

/**
 * Test for the {@link App\Form\DataTransformer\AddressTransformer} class.
 */
class AddressTransformerTest extends TestCase
{
    private ?AddressTransformer $transformer = null;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        $this->transformer = new AddressTransformer();
        parent::setUp();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        $this->transformer = null;
        parent::tearDown();
    }

    public function getReverseTransformValues(): \Generator
    {
        yield [null, null];
        yield [true, null, true];
        yield [25, null, true];
        yield ['user@root.com', new Address('user@root.com')];
        yield ['username <user@root.com>', new Address('user@root.com', 'username')];
        yield ['email-invalid', null, true];
    }

    public function getTransformValues(): \Generator
    {
        yield [null, null];
        yield [true, null, true];
        yield [25, null, true];
        yield [new Address('user@root.com'), 'user@root.com'];
        yield [new Address('user@root.com', 'username'), \htmlentities('username <user@root.com>')];
    }

    /**
     * @param mixed $value
     * @param mixed $expected
     * @dataProvider getReverseTransformValues
     */
    public function testReverseTransform($value, $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(TransformationFailedException::class);
        }
        $actual = $this->transformer->reverseTransform($value);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param mixed $value
     * @param mixed $expected
     * @dataProvider getTransformValues
     */
    public function testTransform($value, $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(TransformationFailedException::class);
        }
        $actual = $this->transformer->transform($value);
        $this->assertEquals($expected, $actual);
    }

    public function testTransformerNotNull(): void
    {
        $this->assertNotNull($this->transformer);
    }
}
