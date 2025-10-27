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

namespace App\Tests\Database;

use App\Database\AbstractDatabase;
use App\Database\LogDatabase;
use App\Entity\Log;
use PHPUnit\Framework\TestCase;

final class LogDatabaseTest extends TestCase
{
    public function testInsertLog(): void
    {
        $database = new LogDatabase(AbstractDatabase::IN_MEMORY);

        $actual = $database->getRecordsCount('sy_Log');
        self::assertSame(0, $actual);

        $log = Log::instance(1)
            ->setMessage('Message');
        $actual = $database->insertLog($log);
        self::assertTrue($actual);

        $actual = $database->getRecordsCount('sy_Log');
        self::assertSame(1, $actual);

        $database->close();
    }
}
