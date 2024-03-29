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

namespace App\Command;

use App\Entity\Calculation;
use App\Faker\Generator;
use App\Repository\CalculationRepository;
use App\Service\FakerService;
use App\Service\SuspendEventListenerService;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to anonymous customer and description in calculations.
 */
#[AsCommand(name: 'app:anonymous', description: 'Anonymous customer and description in calculations.')]
class AnonymousCommand extends Command
{
    private const OPTION_DRY_RUN = 'dry-run';

    public function __construct(
        private readonly SuspendEventListenerService $listener,
        private readonly CalculationRepository $repository,
        private readonly FakerService $service
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(self::OPTION_DRY_RUN, 'd', InputOption::VALUE_NONE, 'Simulate update without flush change to the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = $this->repository->count();
        if (0 === $count) {
            $io->writeln('<info>No calculation to update.</info>');

            return Command::SUCCESS;
        }

        $dryRun = $input->getOption(self::OPTION_DRY_RUN);
        $this->listener->suspendListeners(function () use ($io, $count, $dryRun): void {
            $time = \time();
            $company = true;
            $query = $this->createQuery();
            $generator = $this->createGenerator();
            $io->writeln("Start update calculations\n");
            /** @psalm-var Calculation $calculation */
            foreach ($io->progressIterate($query->toIterable(), $count) as $calculation) {
                $this->updateCalculation($generator, $calculation, $company);
                $company = !$company;
            }
            $io->writeln('End update calculations.');

            if ($dryRun) { // @phpstan-ignore-line
                $io->writeln(\sprintf('Simulate updated %d calculations.', $count));
            } else {
                $io->writeln('Save change to database.');
                $this->repository->flush();
                $io->writeln(\sprintf('Updated %d calculations successfully.', $count));
            }
            $io->writeln(\sprintf('Duration: %d seconds.', \time() - $time));
        });

        return Command::SUCCESS;
    }

    private function createGenerator(): Generator
    {
        return $this->service->getGenerator();
    }

    private function createQuery(): Query
    {
        return $this->repository
            ->createQueryBuilder('c')
            ->getQuery();
    }

    private function updateCalculation(Generator $generator, Calculation $calculation, bool $company): void
    {
        $customer = $company ? $generator->company() : $generator->name();
        $calculation->setCustomer($customer)
            ->setDescription($generator->catchPhrase());
    }
}
