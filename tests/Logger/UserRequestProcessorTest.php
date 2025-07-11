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

namespace App\Tests\Logger;

use App\Entity\User;
use App\Logger\UserRequestProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\DatePoint;

class UserRequestProcessorTest extends TestCase
{
    public function testInvokeWithoutUser(): void
    {
        $security = $this->createSecurity();
        $processor = new UserRequestProcessor($security);
        $record = $processor($this->createRecord());

        $extra = $record->extra;
        self::assertEmpty($extra);
    }

    public function testInvokeWithUser(): void
    {
        $user = new User();
        $user->setUsername('user-name');
        $security = $this->createSecurity($user);
        $processor = new UserRequestProcessor($security);
        $record = $processor($this->createRecord());

        $extra = $record->extra;
        self::assertCount(1, $extra);
        self::assertArrayHasKey('user', $extra);

        $actual = $extra['user'];
        $expected = $user->getUserIdentifier();
        self::assertSame($expected, $actual);
    }

    private function createRecord(): LogRecord
    {
        return new LogRecord(
            new DatePoint(),
            'channel',
            Level::Debug,
            'message'
        );
    }

    private function createSecurity(?User $user = null): MockObject&Security
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')
            ->willReturn($user);

        return $security;
    }
}
