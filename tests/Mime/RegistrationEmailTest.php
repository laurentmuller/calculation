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

namespace App\Tests\Mime;

use App\Mime\RegistrationEmail;
use PHPUnit\Framework\TestCase;

class RegistrationEmailTest extends TestCase
{
    public function testConstructor(): void
    {
        $actual = RegistrationEmail::create()
            ->getHtmlTemplate();
        $expected = 'notification/registration.html.twig';
        self::assertSame($expected, $actual);
    }
}
