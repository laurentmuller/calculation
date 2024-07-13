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

namespace App\Tests\Model;

use App\Enums\StrengthLevel;
use App\Model\PasswordQuery;
use PHPUnit\Framework\TestCase;

class PasswordQueryTest extends TestCase
{
    public function testConstruct(): void
    {
        $query = new PasswordQuery();
        self::assertSame('', $query->password);
        self::assertSame(StrengthLevel::NONE, $query->strength);
        self::assertNull($query->email);
        self::assertNull($query->user);

        $query = new PasswordQuery(
            'password',
            StrengthLevel::MEDIUM,
            'email',
            'user'
        );
        self::assertSame('password', $query->password);
        self::assertSame(StrengthLevel::MEDIUM, $query->strength);
        self::assertSame('email', $query->email);
        self::assertSame('user', $query->user);
    }

    public function testGetInputs(): void
    {
        $query = new PasswordQuery();
        $actual = $query->getInputs();
        self::assertSame([], $actual);

        $query = new PasswordQuery(email: 'email');
        $actual = $query->getInputs();
        self::assertSame(['email'], $actual);

        $query = new PasswordQuery(user: 'user');
        $actual = $query->getInputs();
        self::assertSame(['user'], $actual);

        $query = new PasswordQuery(email: 'email', user: 'user');
        $actual = $query->getInputs();
        self::assertSame(['email', 'user'], $actual);
    }
}
