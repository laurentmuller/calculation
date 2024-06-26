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

use App\Command\AnonymousCommand;
use App\Entity\Calculation;
use App\Tests\DatabaseTrait;
use App\Tests\EntityTrait\CalculationTrait;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AnonymousCommand::class)]
class AnonymousCommandTest extends CommandTestCase
{
    use CalculationTrait;
    use DatabaseTrait;

    private const COMMAND_NAME = 'app:anonymous';

    /**
     * @throws ORMException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->deleteEntitiesByClass(Calculation::class);
    }

    /**
     * @throws ORMException
     */
    public function testExecute(): void
    {
        $this->getCalculation();

        $expected = [
            'Start update calculations',
            'End update calculations.',
            'Save change to database.',
            'Updated',
            'successfully',
            'Duration',
        ];
        $output = $this->execute(self::COMMAND_NAME);
        $this->validate($output, $expected);
    }

    /**
     * @throws ORMException
     */
    public function testExecuteDryRun(): void
    {
        $this->getCalculation();

        $expected = [
            'Start update calculations',
            'End update calculations.',
            'Simulate updated',
            'Duration',
        ];
        $input = [
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        $this->validate($output, $expected);
    }

    public function testExecuteDryRunEmpty(): void
    {
        $expected = 'No calculation to update.';
        $input = [
            '--dry-run' => true,
        ];
        $output = $this->execute(self::COMMAND_NAME, $input);
        $this->validate($output, $expected);
    }

    public function testExecuteEmpty(): void
    {
        $expected = 'No calculation to update.';
        $output = $this->execute(self::COMMAND_NAME);
        $this->validate($output, $expected);
    }
}
