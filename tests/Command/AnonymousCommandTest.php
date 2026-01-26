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

final class AnonymousCommandTest extends CommandTestCase
{
    use CalculationTrait;
    use DatabaseTrait;

    private const string COMMAND_NAME = 'app:anonymous';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->deleteEntitiesByClass(Calculation::class);
    }

    public function testExecute(): void
    {
        $this->getCalculation();
        $output = $this->execute();
        self::assertOutputContainsString(
            $output,
            'Start update calculations',
            'End update calculations.',
            'Save change to database.',
            'Updated',
            'successfully',
            'Duration',
        );
    }

    public function testExecuteDryRun(): void
    {
        $this->getCalculation();
        $input = ['--dry-run' => true];
        $output = $this->execute($input);
        self::assertOutputContainsString(
            $output,
            'Start update calculations',
            'End update calculations.',
            'Simulate updated',
            'Duration'
        );
    }

    public function testExecuteDryRunEmpty(): void
    {
        $input = ['--dry-run' => true];
        $output = $this->execute($input);
        self::assertOutputContainsString($output, 'No calculation to update.');
    }

    public function testExecuteEmpty(): void
    {
        $output = $this->execute();
        self::assertOutputContainsString($output, 'No calculation to update.');
    }

    #[\Override]
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }
}
