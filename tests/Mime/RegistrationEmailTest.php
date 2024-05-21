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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RegistrationEmail::class)]
class RegistrationEmailTest extends TestCase
{
    public function testConstructor(): void
    {
        $mail = new RegistrationEmail();
        $actual = $mail->getHtmlTemplate();
        $expected = 'notification/registration.html.twig';
        self::assertSame($expected, $actual);
    }
}
