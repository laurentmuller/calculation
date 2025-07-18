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

use App\Entity\Calculation;
use App\Tests\DatabaseTrait;
use App\Tests\EntityTrait\CalculationTrait;
use Symfony\Component\Console\Command\Command;

class UcFirstCommandTest extends CommandTestCase
{
    use CalculationTrait;
    use DatabaseTrait;

    private const COMMAND_NAME = 'app:uc-first';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->deleteEntitiesByClass(Calculation::class);
    }

    public function testAskFieldName(): void
    {
        $expected = "Select a field name for the 'Calculation' entity:";
        $input = [
            '--class' => Calculation::class,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString($expected, $output);
    }

    public function testExecute(): void
    {
        $this->getCalculation(customer: 'customer');

        $expected = [
            'Updated 1 values of 1 entities successfully.',
            'Duration',
        ];
        $input = [
            '--class' => Calculation::class,
            '--field' => 'customer',
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString($expected, $output);
    }

    public function testExecuteDryRun(): void
    {
        $this->getCalculation(customer: 'customer');

        $expected = [
            'Updated 1 values of 1 entities successfully.',
            'No change saved to database.',
            'Duration:',
        ];
        $input = [
            '--class' => Calculation::class,
            '--field' => 'customer',
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString($expected, $output);
    }

    public function testExecuteEmpty(): void
    {
        $expected = 'No entity to update.';
        $input = [
            '--class' => Calculation::class,
            '--field' => 'customer',
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString($expected, $output);
    }

    public function testExecuteEmptyDryRun(): void
    {
        $expected = 'No entity to update.';
        $input = [
            '--class' => Calculation::class,
            '--field' => 'customer',
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        self::assertOutputContainsString($expected, $output);
    }

    public function testExecuteMissingClass(): void
    {
        $input = [
            '--field' => 'customer',
        ];
        $this->executeMissingInput(self::COMMAND_NAME, $input);
    }

    public function testInvalidClassName(): void
    {
        $expected = "Unable to find the 'fake' entity.";
        $input = [
            '--class' => 'fake',
            '--field' => 'fake',
            '--dry-run' => true,
        ];
        $options = [
            'interactive' => false,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input, $options, Command::INVALID);
        self::assertOutputContainsString($expected, $output);
    }

    public function testInvalidFieldName(): void
    {
        $expected = "Unable to find the field 'fake' for the entity 'App\Entity\Calculation'.";
        $input = [
            '--class' => Calculation::class,
            '--field' => 'fake',
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input, [], Command::INVALID);
        self::assertOutputContainsString($expected, $output);
    }

    public function testNotInteractive(): void
    {
        $options = ['interactive' => false];
        $this->execute(self::COMMAND_NAME, [], $options, Command::INVALID);
    }
}
