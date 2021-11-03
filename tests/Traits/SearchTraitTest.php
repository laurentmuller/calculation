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

namespace App\Tests\Traits;

use App\Entity\Customer;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for {@link App\Traits\SearchTrait} class.
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
