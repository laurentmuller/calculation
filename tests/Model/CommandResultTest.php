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

use App\Model\CommandResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;

class CommandResultTest extends TestCase
{
    public function testProperties(): void
    {
        $actual = new CommandResult(Command::SUCCESS, 'content');
        self::assertSame(Command::SUCCESS, $actual->status);
        self::assertSame('content', $actual->content);
    }

    public function testResult(): void
    {
        $actual = new CommandResult(Command::SUCCESS, 'content');
        self::assertTrue($actual->isSuccess());
        $actual = new CommandResult(Command::FAILURE, 'content');
        self::assertFalse($actual->isSuccess());
    }
}
