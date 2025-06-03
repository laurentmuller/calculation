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
        $this->validate($output, $expected);
    }

    public function testExecute(): void
    {
        $this->getCalculation(customer: 'customer');

        $expected = 'Updated 1 values successfully.';
        $input = [
            '--class' => Calculation::class,
            '--field' => 'customer',
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        $this->validate($output, $expected);
    }

    public function testExecuteDryRun(): void
    {
        $this->getCalculation(customer: 'customer');

        $expected = [
            'Updated 1 values successfully.',
            'No change saved to database.',
        ];
        $input = [
            '--class' => Calculation::class,
            '--field' => 'customer',
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        $this->validate($output, $expected);
    }

    public function testExecuteEmpty(): void
    {
        $expected = 'No value updated.';
        $input = [
            '--class' => Calculation::class,
            '--field' => 'customer',
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        $this->validate($output, $expected);
    }

    public function testExecuteEmptyDryRun(): void
    {
        $expected = 'No value updated.';
        $input = [
            '--class' => Calculation::class,
            '--field' => 'customer',
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        $this->validate($output, $expected);
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
        $expected = "Unable to find the entity 'fake'.";
        $input = [
            '--class' => 'fake',
            '--field' => 'fake',
            '--dry-run' => true,
        ];
        $options = ['interactive' => false];
        $output = $this->execute(self::COMMAND_NAME, $input, $options, Command::INVALID);
        $this->validate($output, $expected);
    }

    public function testInvalidFieldName(): void
    {
        $expected = "Unable to find field 'fake' for the entity 'App\Entity\Calculation'.";
        $input = [
            '--class' => Calculation::class,
            '--field' => 'fake',
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input, [], Command::INVALID);
        $this->validate($output, $expected);
    }

    public function testNoFieldNameNoInteractive(): void
    {
        $expected = "No field selected for the entity 'App\Entity\Calculation'.";
        $input = [
            '--class' => Calculation::class,
            '--dry-run' => true,
        ];
        $options = ['interactive' => false];
        $output = $this->execute(self::COMMAND_NAME, $input, $options, Command::INVALID);
        $this->validate($output, $expected);
    }

    public function testNotInteractive(): void
    {
        $options = ['interactive' => false];
        $this->execute(self::COMMAND_NAME, [], $options, Command::INVALID);
    }
}
