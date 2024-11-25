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

namespace App\Tests\Service;

use App\Service\DictionaryService;
use PHPUnit\Framework\TestCase;

class DictionaryServiceTest extends TestCase
{
    public function testGetRandomWord(): void
    {
        $service = new DictionaryService();
        $actual = $service->getRandomWord();
        self::assertTrue(\ctype_upper($actual));
    }
}
