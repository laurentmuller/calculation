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

use App\Entity\Calculation;
use App\Entity\CalculationGroup;
use App\Entity\CalculationState;

#[\PHPUnit\Framework\Attributes\CoversClass(Calculation::class)]
class CalculationTest extends AbstractEntityValidatorTestCase
{
    public function testFields(): void
    {
        $calculation = new Calculation();
        self::assertSame(1.0, $calculation->getGroupsMargin());
        self::assertSame(0.0, $calculation->getGroupsMarginAmount());
        self::assertSame(0.0, $calculation->getGroupsTotal());

        self::assertSame(0.0, $calculation->getItemsTotal());
        self::assertSame(0, $calculation->getLinesCount());

        self::assertSame(0.0, $calculation->getOverallMargin());
        self::assertSame(0.0, $calculation->getOverallMarginAmount());
        self::assertSame(0.0, $calculation->getOverallTotal());

        self::assertSame(0.0, $calculation->getTotalNet());

        self::assertSame(0.0, $calculation->getUserMargin());
        self::assertSame(0.0, $calculation->getUserMarginAmount());
        self::assertSame(0.0, $calculation->getUserMarginTotal());

        self::assertFalse($calculation->hasDuplicateItems());
        self::assertFalse($calculation->hasEmptyItems());
        self::assertFalse($calculation->isSortable());

        self::assertTrue($calculation->isEditable());
        self::assertTrue($calculation->isEmpty());

        $date = new \DateTime();
        $calculation->setDate($date);
        self::assertSame($date, $calculation->getDate());

        $description = 'description';
        self::assertNull($calculation->getDescription());
        $calculation->setDescription($description);
        self::assertSame($description, $calculation->getDescription());
    }

    public function testGroup(): void
    {
        $group = new CalculationGroup();
        $calculation = new Calculation();
        self::assertCount(0, $calculation->getGroups());
        self::assertSame(0, $calculation->getGroupsCount());
        self::assertSame(0, $calculation->getCategoriesCount());
        self::assertFalse($calculation->contains($group));

        $calculation->addGroup($group);
        self::assertCount(1, $calculation->getGroups());
        self::assertSame(1, $calculation->getGroupsCount());
        self::assertTrue($calculation->contains($group));

        $calculation->removeGroup($group);
        self::assertCount(0, $calculation->getGroups());
        self::assertSame(0, $calculation->getGroupsCount());
        self::assertFalse($calculation->contains($group));
    }

    public function testInvalidAll(): void
    {
        $calculation = new Calculation();
        $results = $this->validate($calculation, 3);
        $this->validatePaths($results, 'customer', 'description', 'state');
    }

    public function testInvalidCustomer(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('my description')
            ->setState($this->getState());
        $this->validate($calculation, 1);
    }

    public function testInvalidDescription(): void
    {
        $calculation = new Calculation();
        $calculation->setCustomer('my customer')
            ->setState($this->getState());
        $results = $this->validate($calculation, 1);
        $this->validatePaths($results, 'description');
    }

    public function testInvalidState(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('my description')
            ->setCustomer('my customer');
        $results = $this->validate($calculation, 1);
        $this->validatePaths($results, 'state');
    }

    public function testState(): void
    {
        $state = new CalculationState();
        $state->setCode('code')
            ->setColor('color');

        $calculation = new Calculation();
        self::assertNull($calculation->getState());
        self::assertNull($calculation->getStateCode());
        self::assertNull($calculation->getStateColor());

        $calculation->setState($state);
        self::assertNotNull($calculation->getState());
        self::assertSame('code', $calculation->getStateCode());
        self::assertSame('color', $calculation->getStateColor());
    }

    public function testValid(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('my description')
            ->setCustomer('my customer')
            ->setState($this->getState());
        $this->validate($calculation);
    }

    private function getState(): CalculationState
    {
        $state = new CalculationState();
        $state->setCode('my code');

        return $state;
    }
}
