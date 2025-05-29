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

use App\Command\SymfonyStyle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;

class SymfonyStyleTest extends TestCase
{
    private ArrayInput $input;

    #[\Override]
    protected function setUp(): void
    {
        $this->input = new ArrayInput([]);
    }

    public function testFormatDuration(): void
    {
        $style = $this->createStyle();
        $actual = $style->formatDuration(\time() - 4000);
        self::assertSame('1 h', $actual);
    }

    public function testGetArrayOption(): void
    {
        $name = 'key';
        $expected = ['key' => 'value'];
        $option = $this->createInputOption($name, $expected);
        $style = $this->createStyle([$option]);
        $actual = $style->getArrayOption($name);
        self::assertSame($expected, $actual);
    }

    public function testGetBoolOption(): void
    {
        $name = 'key';
        $option = $this->createInputOption($name, true);
        $style = $this->createStyle([$option]);
        $actual = $style->getBoolOption($name);
        self::assertTrue($actual);
    }

    public function testGetIntOption(): void
    {
        $name = 'key';
        $expected = 25;
        $option = $this->createInputOption($name, $expected);
        $style = $this->createStyle([$option]);
        $actual = $style->getIntOption($name);
        self::assertSame($expected, $actual);
    }

    public function testGetOption(): void
    {
        $name = 'key';
        $expected = ['key' => 'value'];
        $option = $this->createInputOption($name, $expected);
        $style = $this->createStyle([$option]);
        $actual = $style->getOption($name);
        self::assertSame($expected, $actual);
    }

    public function testGetStringArgument(): void
    {
        $name = 'key';
        $expected = 'value';
        $parameters = [$name => $expected];
        $definition = new InputDefinition();
        $argument = new InputArgument($name, InputOption::VALUE_REQUIRED, default: $expected);
        $definition->addArgument($argument);
        $style = $this->createStyle($definition, $parameters);
        $actual = $style->getStringArgument($name);
        self::assertSame('value', $actual);
    }

    public function testProperties(): void
    {
        $style = $this->createStyle();
        $input = $style->getInput();
        self::assertInstanceOf(ArrayInput::class, $input);
        $output = $style->getOutput();
        self::assertInstanceOf(NullOutput::class, $output);
        self::assertTrue($style->isInteractive());
    }

    private function createInputOption(string $name, string|bool|int|float|array|null $default = null): InputOption
    {
        return new InputOption($name, mode: InputOption::VALUE_OPTIONAL, default: $default);
    }

    private function createStyle(InputDefinition|array $definition = [], array $parameters = []): SymfonyStyle
    {
        if (\is_array($definition)) {
            $definition = new InputDefinition($definition);
        }
        $this->input = new ArrayInput($parameters, $definition);
        $output = new NullOutput();

        return new SymfonyStyle($this->input, $output);
    }
}
