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

namespace App\Tests\Traits;

use App\Entity\Customer;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for SearchTrait.
 *
 * @author Laurent Muller
 */
class SearchTraitTest extends TestCase
{
    public function getValues(): array
    {
        return [
            ['sarl', 'sarl'],
            ['Sarl', 'sarl'],
            ['sarl', 'Sarl'],
            ['sàrl', 'Sarl'],
            ['sarl', 'Sàrl'],
            ['sarl', 'Särl'],
            ['surl', 'Sürl'],
            ['sÕrl', 'sorl'],
            ['prefix sarl', 'sarl'],
            ['prefix sarl', 'sàrl'],
            ['prefix sàrl', 'sarl'],
            ['prefix sàrl suffix', 'sarl'],
            ['sarl', 'wrong', false],
            ['Wrong', 'aaa', false],
            ['Æ', 'a'],
            ['Æ', 'e'],
            ['Æ', 'ae'],
            ['æ', 'a'],
            ['æ', 'e'],
            ['æ', 'ae'],
        ];
    }

    /**
     * @dataProvider getValues
     */
    public function testMatch(string $term, string $query, bool $expected = true): void
    {
        $c = new Customer();
        $c->setCompany($term);
        $result = $c->match($query);
        $this->assertEquals($expected, $result);
    }
}
