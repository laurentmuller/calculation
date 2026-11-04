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

namespace App\Tests\Entity;

use App\Entity\CalculationState;
use fpdf\Color\PdfRgbColor;

final class CalculationStateTest extends EntityValidatorTestCase
{
    public function testCalculations(): void
    {
        $state = new CalculationState();
        self::assertSame(0, $state->countCalculations());
        self::assertEmpty($state->getCalculations());
        self::assertFalse($state->hasCalculations());
    }

    public function testClone(): void
    {
        $state = new CalculationState();
        $state->withCode('code');

        $clone = $state->clone();
        self::assertSame($state->code, $clone->code);

        $clone = $state->clone('new-state');
        self::assertNotSame($state->code, $clone->code);
        self::assertSame('new-state', $clone->code);
    }

    public function testColor(): void
    {
        $state = new CalculationState();
        self::assertSame('#000000', $state->color);

        $state->withColor('#010203');
        self::assertSame('#010203', $state->color);
    }

    public function testCompare(): void
    {
        $item1 = new CalculationState();
        $item1->withCode('Code1');
        $item2 = new CalculationState();
        $item2->withCode('Code2');
        $actual = $item1->compare($item2);
        self::assertSame(-1, $actual);
    }

    public function testDescription(): void
    {
        $state = new CalculationState();
        self::assertNull($state->description);

        $state->withDescription('description');
        self::assertSame('description', $state->description);
    }

    public function testDuplicate(): void
    {
        $first = new CalculationState();
        $first->withCode('code');

        try {
            $this->saveEntity($first);
            $second = new CalculationState();
            $second->withCode('code');
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'code');
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testEditable(): void
    {
        $state = new CalculationState();
        self::assertTrue($state->editable);

        $state->withEditable(false);
        self::assertFalse($state->editable);
    }

    public function testInvalidCode(): void
    {
        $state = new CalculationState();
        $results = $this->validate($state, 1);
        $this->validatePaths($results, 'code');
    }

    public function testNotDuplicate(): void
    {
        $first = new CalculationState();
        $first->withCode('code');

        try {
            $this->saveEntity($first);
            $second = new CalculationState();
            $second->withCode('code 2');
            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testRgbColor(): void
    {
        $state = new CalculationState();
        $expected = PdfRgbColor::black();
        $actual = $state->getRgbColor();
        self::assertInstanceOf(PdfRgbColor::class, $actual);
        self::assertTrue($expected->equals($actual));

        $state->withColor('#010203');
        $expected = new PdfRgbColor(1, 2, 3);
        $actual = $state->getRgbColor();
        self::assertInstanceOf(PdfRgbColor::class, $actual);
        self::assertTrue($expected->equals($actual));
    }

    public function testValid(): void
    {
        $state = new CalculationState();
        $state->withCode('code');
        $this->validate($state);
    }
}
