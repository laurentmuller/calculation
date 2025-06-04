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
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:anonymous', description: 'Anonymous customer and description in calculations.')]
readonly class AnonymousCommand
{
    public function __construct(
        private SuspendEventListenerService $listener,
        private CalculationRepository $repository,
        private FakerService $service
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Simulate update without flush change to the database.', name: 'dry-run', shortcut: 'd')]
        bool $dryRun = false
    ): int {
        $count = $this->count($io);
        if (0 === $count) {
            return Command::SUCCESS;
        }

        return $this->listener->suspendListeners(fn (): int => $this->update($io, $count, $dryRun));
    }

    private function count(SymfonyStyle $io): int
    {
        $count = $this->repository->count();
        if (0 === $count) {
            $io->info('No calculation to update.');
        }

        return $count;
    }

    private function createGenerator(): Generator
    {
        return $this->service->getGenerator();
    }

    /**
     * @psalm-return Query
     *
     * @phpstan-return Query<Calculation>
     */
    private function createQuery(): Query
    {
        return $this->repository
            ->createQueryBuilder('c')
            ->getQuery();
    }

    private function formatDuration(int $time): string
    {
        return Helper::formatTime(\time() - $time);
    }

    private function update(SymfonyStyle $io, int $count, bool $dryRun): int
    {
        $time = \time();
        $company = true;
        $query = $this->createQuery();
        $generator = $this->createGenerator();
        $io->writeln("Start update calculations\n");
        /** @phpstan-var Calculation $calculation */
        foreach ($io->progressIterate($query->toIterable(), $count) as $calculation) {
            $this->updateCalculation($generator, $calculation, $company);
            $company = !$company;
        }
        $io->writeln('End update calculations.');
        if ($dryRun) {
            $io->success(\sprintf('Simulate updated %d calculations. Duration: %s.', $count, $this->formatDuration($time)));
        } else {
            $io->writeln('Save change to database.');
            $this->repository->flush();
            $io->success(\sprintf('Updated %d calculations successfully. Duration: %s.', $count, $this->formatDuration($time)));
        }

        return Command::SUCCESS;
    }

    private function updateCalculation(Generator $generator, Calculation $calculation, bool $company): void
    {
        $customer = $company ? $generator->company() : $generator->name();
        $calculation->setCustomer($customer)
            ->setDescription($generator->catchPhrase());
    }
}
