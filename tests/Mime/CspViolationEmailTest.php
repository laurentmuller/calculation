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

use App\Mime\CspViolationEmail;
use PHPUnit\Framework\TestCase;

class CspViolationEmailTest extends TestCase
{
    public function testConstructor(): void
    {
        $actual = CspViolationEmail::create()
            ->getHtmlTemplate();
        $expected = 'notification/csp_violation.html.twig';
        self::assertSame($expected, $actual);
    }
}
