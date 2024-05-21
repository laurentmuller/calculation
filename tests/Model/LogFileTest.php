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

use App\Entity\Log;
use App\Model\LogFile;
use App\Tests\Entity\IdTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

#[CoversClass(LogFile::class)]
class LogFileTest extends TestCase
{
    use IdTrait;
    private LogFile $file;

    protected function setUp(): void
    {
        $this->file = new LogFile(__DIR__ . '/file.log');
    }

    /**
     * @throws \ReflectionException
     */
    public function testAddLog(): void
    {
        self::assertCount(0, $this->file);
        self::assertCount(0, $this->file->getLevels());
        self::assertCount(0, $this->file->getChannels());

        $log = $this->createLog();
        $this->file->addLog($log);
        self::assertCount(1, $this->file);
        self::assertCount(1, $this->file->getLevels());
        self::assertCount(1, $this->file->getChannels());
    }

    public function testFile(): void
    {
        $expected = __DIR__ . '/file.log';
        $actual = $this->file->getFile();
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetLog(): void
    {
        $log = $this->createLog();
        $this->file->addLog($log);
        $actual = $this->file->getLog(0);
        self::assertNull($actual);
        $actual = $this->file->getLog(1);
        self::assertSame($log, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetLogs(): void
    {
        $log = $this->createLog();
        $this->file->addLog($log);
        $actual = $this->file->getLogs();
        self::assertNotEmpty($actual);
        self::assertSame([1 => $log], $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testIsEmpty(): void
    {
        self::assertTrue($this->file->isEmpty());
        $this->file->addLog($this->createLog());
        self::assertFalse($this->file->isEmpty());
    }

    /**
     * @throws \ReflectionException
     */
    public function testSort(): void
    {
        $this->file->addLog($this->createLog());
        $this->file->sort();
        self::assertFalse($this->file->isEmpty());
    }

    /**
     * @throws \ReflectionException
     */
    private function createLog(): Log
    {
        $log = new Log();
        $log->setChannel('channel')
            ->setLevel(LogLevel::INFO);

        return self::setId($log);
    }
}
