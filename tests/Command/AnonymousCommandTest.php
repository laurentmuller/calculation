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
use App\Entity\CalculationState;
use App\Interfaces\EntityInterface;
use App\Tests\Web\AbstractAuthenticateWebTestCase;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[\PHPUnit\Framework\Attributes\CoversClass(AnonymousCommand::class)]
class AnonymousCommandTest extends AbstractAuthenticateWebTestCase
{
    /**
     * @throws ORMException
     */
    public function testExecute(): void
    {
        $state = $this->addState();
        $calculation = $this->addCalculation($state);

        try {
            $expected = [
                'Start update calculations',
                'End update calculations.',
                'Save change to database.',
                'Updated',
                'successfully',
                'Duration',
            ];
            $output = $this->execute();
            $this->validate($output, $expected);
        } finally {
            $this->removeEntity($calculation);
            $this->removeEntity($state);
        }
    }

    /**
     * @throws ORMException
     */
    public function testExecuteDryRun(): void
    {
        $state = $this->addState();
        $calculation = $this->addCalculation($state);

        try {
            $expected = [
                'Start update calculations',
                'End update calculations.',
                'Simulate updated',
                'Duration',
            ];
            $output = $this->execute(['--dry-run' => true]);
            $this->validate($output, $expected);
        } finally {
            $this->removeEntity($calculation);
            $this->removeEntity($state);
        }
    }

    public function testExecuteDryRunEmpty(): void
    {
        $output = $this->execute(['--dry-run' => true]);
        $expected = [
            'No calculation to update.',
        ];
        $this->validate($output, $expected);
    }

    public function testExecuteEmpty(): void
    {
        $output = $this->execute();
        $expected = [
            'No calculation to update.',
        ];
        $this->validate($output, $expected);
    }

    /**
     * @throws ORMException
     */
    private function addCalculation(CalculationState $state): Calculation
    {
        $calculation = new Calculation();
        $calculation->setCustomer('customer')
            ->setDescription('description')
            ->setState($state);

        return $this->persistEntity($calculation);
    }

    /**
     * @throws ORMException
     */
    private function addState(): CalculationState
    {
        $state = new CalculationState();
        $state->setCode('code');

        return $this->persistEntity($state);
    }

    private function execute(array $input = []): string
    {
        $application = new Application($this->client->getKernel());
        $command = $application->find('app:anonymous');

        $tester = new CommandTester($command);
        $result = $tester->execute($input);
        self::assertSame(Command::SUCCESS, $result);
        $tester->assertCommandIsSuccessful();

        return $tester->getDisplay();
    }

    /**
     * @template T of EntityInterface
     *
     * @psalm-param T $entity
     *
     * @psalm-return T
     *
     * @throws ORMException
     */
    private function persistEntity(EntityInterface $entity): EntityInterface
    {
        $manager = $this->getManager();
        $manager->persist($entity);
        $manager->flush();

        return $entity;
    }

    /**
     * @throws ORMException
     */
    private function removeEntity(EntityInterface $entity): void
    {
        $manager = $this->getManager();
        $manager->remove($entity);
        $manager->flush();
    }

    /**
     * @psalm-param string[] $expected
     */
    private function validate(string $output, array $expected): void
    {
        foreach ($expected as $value) {
            self::assertStringContainsString($value, $output);
        }
    }
}
