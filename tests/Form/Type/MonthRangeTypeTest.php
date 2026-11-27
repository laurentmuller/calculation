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

namespace App\Tests\Form\Type;

use App\Form\Type\MonthRangeType;
use App\Model\MonthRange;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Form\Test\TypeTestCase;

#[AllowMockObjectsWithoutExpectations]
final class MonthRangeTypeTest extends TypeTestCase
{
    public function testChildren(): void
    {
        $date = new DatePoint();
        $options = [
            'min_date' => $date,
            'max_date' => $date,
        ];
        $monthRange = new MonthRange();
        $view = $this->factory->create(MonthRangeType::class, $monthRange, $options)
            ->createView();
        $children = $view->children;
        self::assertArrayHasKey('from', $children);
        self::assertArrayHasKey('to', $children);
    }

    public function testSubmit(): void
    {
        $date = new DatePoint();
        $options = [
            'min_date' => $date->sub(new \DateInterval('P2M')),
            'max_date' => $date->add(new \DateInterval('P2M')),
        ];
        $monthRange = new MonthRange();
        $form = $this->factory->create(MonthRangeType::class, $monthRange, $options);
        $form->submit([
            'from' => $date->format('Y-m-01'),
            'to' => $date->format('Y-m-t'),
        ]);
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());
    }
}
