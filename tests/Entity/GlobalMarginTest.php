<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
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
