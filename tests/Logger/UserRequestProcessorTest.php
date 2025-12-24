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
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class UserRequestProcessorTest extends TestCase
{
    public function testWithoutUser(): void
    {
        $processor = $this->createProcessor();
        $record = $processor($this->createRecord());

        $extra = $record->extra;
        self::assertEmpty($extra);
    }

    public function testWithUser(): void
    {
        $user = (new User())->setUsername('user-name');
        $processor = $this->createProcessor($user);
        $record = $processor($this->createRecord());

        $extra = $record->extra;
        self::assertCount(1, $extra);
        self::assertArrayHasKey('user', $extra);
        self::assertSame('user-name', $extra['user']);
    }

    private function createProcessor(?User $user = null): UserRequestProcessor
    {
        $security = $this->createMock(Security::class);
        $security->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        return new UserRequestProcessor($security);
    }

    private function createRecord(): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'Channel',
            level: Level::Debug,
            message: 'Message'
        );
    }
}
