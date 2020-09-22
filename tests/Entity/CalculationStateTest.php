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

use App\Entity\CalculationState;

/**
 * Unit test for validate CalculationState constraints.
 *
 * @author Laurent Muller
 */
class CalculationStateTest extends EntityValidatorTest
{
    public function testDuplicate(): void
    {
        $first = new CalculationState();
        $first->setCode('code');

        try {
            $this->saveEntity($first);

            $second = new CalculationState();
            $second->setCode('code');

            $this->validate($second, 1);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testInvalidAll(): void
    {
        $state = new CalculationState();
        $this->validate($state, 1);
    }

    public function testNotDuplicate(): void
    {
        $first = new CalculationState();
        $first->setCode('code');

        try {
            $this->saveEntity($first);

            $second = new CalculationState();
            $second->setCode('code 2');

            $this->validate($second, 0);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $state = new CalculationState();
        $state->setCode('code');
        $this->validate($state, 0);
    }
}
