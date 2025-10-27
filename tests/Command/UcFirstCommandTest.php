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
use Symfony\Component\Console\Exception\MissingInputException;

final class UcFirstCommandTest extends CommandTestCase
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
        $input = [
            '--class' => Calculation::class,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString($output, "Select a field name for the 'Calculation' entity:");
    }

    public function testExecute(): void
    {
        $this->getCalculation(customer: 'customer');
        $input = [
            '--class' => Calculation::class,
            '--field' => 'customer',
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            'Updated 1 values of 1 entities successfully.',
            'Duration'
        );
    }

    public function testExecuteDryRun(): void
    {
        $this->getCalculation(customer: 'customer');
        $input = [
            '--class' => Calculation::class,
            '--field' => 'customer',
            '--dry-run' => true,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            'Updated 1 values of 1 entities successfully.',
            'No change saved to database.',
            'Duration:'
        );
    }

    public function testExecuteEmpty(): void
    {
        $input = [
            '--class' => Calculation::class,
            '--field' => 'customer',
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString($output, 'No entity to update.');
    }

    public function testExecuteEmptyDryRun(): void
    {
        $input = [
            '--class' => Calculation::class,
            '--field' => 'customer',
            '--dry-run' => true,
        ];
        $output = $this->execute($input);
        self::assertOutputContainsString($output, 'No entity to update.');
    }

    public function testExecuteMissingClass(): void
    {
        $input = [
            '--field' => 'customer',
        ];
        self::expectException(MissingInputException::class);
        $this->execute($input);
    }

    public function testInvalidClassName(): void
    {
        $input = [
            '--class' => 'fake',
            '--field' => 'fake',
            '--dry-run' => true,
        ];
        $options = [
            'interactive' => false,
        ];
        $output = $this->executeInvalid($input, $options);
        self::assertOutputContainsString($output, "Unable to find the 'fake' entity.");
    }

    public function testInvalidFieldName(): void
    {
        $input = [
            '--class' => Calculation::class,
            '--field' => 'fake',
            '--dry-run' => true,
        ];
        $output = $this->executeInvalid($input);
        self::assertOutputContainsString(
            $output,
            "Unable to find the field 'fake' for the entity 'App\Entity\Calculation'."
        );
    }

    public function testNotInteractive(): void
    {
        $options = ['interactive' => false];
        $this->executeInvalid(options: $options);
    }

    #[\Override]
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }
}
