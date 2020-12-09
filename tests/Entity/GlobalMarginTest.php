<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\GlobalMargin;

/**
 * Unit test for validate global margin constraints.
 *
 * @author Laurent Muller
 */
class GlobalMarginTest extends EntityValidatorTest
{
    public function testMarginLessZero(): void
    {
        $margin = $this->getGlobalMargin(0, 1, -1);
        $this->validate($margin, 1);
    }

    public function testMaxLessMin(): void
    {
        $margin = $this->getGlobalMargin(10, 9, 0);
        $this->validate($margin, 1);
    }

    public function testMinLessZero(): void
    {
        $margin = $this->getGlobalMargin(-1, 1, 0);
        $this->validate($margin, 1);
    }

    public function testValid(): void
    {
        $margin = $this->getGlobalMargin(0, 10, 0);
        $this->validate($margin, 0);
    }

    private function getGlobalMargin(float $minimum, float $maximum, float $margin): GlobalMargin
    {
        $entity = new GlobalMargin();
        $entity->setValues($minimum, $maximum, $margin);

        return $entity;
    }
}
