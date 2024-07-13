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

namespace App\Tests\Command;

use App\Command\LoggerTrait;
use App\Command\SymfonyStyle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class LoggerTraitTest extends TestCase
{
    use LoggerTrait;

    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
    }

    public function testLoggerIsNotVerbose(): void
    {
        $this->io = $this->createStyle();
        self::assertFalse($this->isVerbose());
        self::assertFalse($this->isVeryVerbose());
    }

    public function testLoggerIsNotVeryVerbose(): void
    {
        $this->io = $this->createStyle();
        self::assertFalse($this->isVeryVerbose());
    }

    public function testLoggerIsVerbose(): void
    {
        $this->io = $this->createStyle(OutputInterface::VERBOSITY_VERBOSE);
        self::assertTrue($this->isVerbose());
        self::assertFalse($this->isVeryVerbose());
    }

    public function testLoggerIsVeryVerbose(): void
    {
        $this->io = $this->createStyle(OutputInterface::VERBOSITY_VERY_VERBOSE);
        self::assertTrue($this->isVerbose());
        self::assertTrue($this->isVeryVerbose());
    }

    public function testWriteError(): void
    {
        $expected = 'Write error message';
        $this->io = $this->createStyle();
        $this->writeError($expected);
        self::assertOutputContainsString($this->output, $expected);
    }

    public function testWriteln(): void
    {
        $expected = 'Write Line';
        $this->io = $this->createStyle();
        $this->writeln($expected);
        self::assertOutputContainsString($this->output, $expected);
    }

    public function testWriteNote(): void
    {
        $expected = 'Write Note';
        $this->io = $this->createStyle();
        $this->writeNote($expected);
        self::assertOutputContainsString($this->output, $expected);
    }

    public function testWriteSuccess(): void
    {
        $expected = 'Write Success';
        $this->io = $this->createStyle();
        $this->writeSuccess($expected);
        self::assertOutputContainsString($this->output, $expected);
    }

    public function testWriteVerbose(): void
    {
        $expected = 'Write Verbose';
        $this->io = $this->createStyle(OutputInterface::VERBOSITY_VERBOSE);
        $this->writeVerbose($expected);
        self::assertOutputContainsString($this->output, $expected);
    }

    public function testWriteVeryVerbose(): void
    {
        $expected = 'Write Very Verbose';
        $this->io = $this->createStyle(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $this->writeVeryVerbose($expected);
        self::assertOutputContainsString($this->output, $expected);
    }

    public function testWriteWarning(): void
    {
        $expected = 'Write Warning';
        $this->io = $this->createStyle();
        $this->writeWarning($expected);
        self::assertOutputContainsString($this->output, $expected);
    }

    protected static function assertOutputContainsString(BufferedOutput $output, string $expected): void
    {
        $actual = $output->fetch();
        self::assertStringContainsString($expected, $actual);
    }

    private function createStyle(int $verbosity = OutputInterface::VERBOSITY_NORMAL): SymfonyStyle
    {
        $input = new ArrayInput([]);
        $this->output = new BufferedOutput($verbosity);

        return new SymfonyStyle($input, $this->output);
    }
}
