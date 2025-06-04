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

use App\Interfaces\EntityInterface;
use App\Service\SuspendEventListenerService;
use App\Utils\StringUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @phpstan-type EntityType = array{name: string, fields: non-empty-array<string>}
 */
#[AsCommand(name: 'app:uc-first', description: 'Convert the first character to uppercase for the given entity and field.')]
class UcFirstCommand
{
    /** @phpstan-var array<class-string, EntityType> */
    private array $entities = [];

    public function __construct(
        private readonly SuspendEventListenerService $listener,
        private readonly EntityManagerInterface $manager
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'The entity class to update.', shortcut: 'c')]
        ?string $class = null,
        #[Option(description: 'The field name to update.', shortcut: 'f')]
        ?string $field = null,
        #[Option(description: 'Add a point (".") at the end of the converted value.', shortcut: 'p')]
        bool $point = false,
        #[Option(description: 'Simulate update without flush change to the database.', name: 'dry-run', shortcut: 'd')]
        bool $dryRun = false
    ): int {
        $class = $this->validateClass($io, $class);
        if (!StringUtils::isString($class)) {
            return Command::INVALID;
        }
        $field = $this->validateField($io, $class, $field);
        if (!StringUtils::isString($field)) {
            return Command::INVALID;
        }
        $total = $this->count($io, $class);
        if (0 === $total) {
            return Command::SUCCESS;
        }

        return $this->listener->suspendListeners(fn (): int => $this->update($io, $class, $field, $total, $point, $dryRun));
    }

    /**
     * @return class-string|null
     */
    private function askClassName(SymfonyStyle $io): ?string
    {
        $entities = $this->getEntities();
        $choices = \array_column($entities, 'name');
        $question = new ChoiceQuestion('Select an entity:', $choices);
        $question->setMaxAttempts(1)
            ->setErrorMessage('No entity selected.');

        /** @phpstan-var ?string $class */
        $class = $io->askQuestion($question);
        if (!StringUtils::isString($class)) {
            return null;
        }

        foreach ($entities as $key => $entity) {
            if ($entity['name'] === $class) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @phpstan-param class-string $class
     */
    private function askFieldName(SymfonyStyle $io, string $class): ?string
    {
        $entry = $this->getEntities()[$class];
        $name = $entry['name'];
        $choices = $entry['fields'];
        $question = new ChoiceQuestion("Select a field name for the '$name' entity:", $choices, 0);
        $question->setMaxAttempts(1)
            ->setErrorMessage("No field selected for the '$name' entity.");

        /** @phpstan-var ?string $field */
        $field = $io->askQuestion($question);
        if (!StringUtils::isString($field)) {
            return null;
        }

        return $field;
    }

    private function convert(?string $str, bool $point): ?string
    {
        if (!StringUtils::isString($str)) {
            return $str;
        }
        $str = \ucfirst($str);
        if ($point && !\str_ends_with($str, '.')) {
            $str .= '.';
        }

        return $str;
    }

    /**
     * @phpstan-param class-string $class
     */
    private function count(SymfonyStyle $io, string $class): int
    {
        $count = $this->manager->getRepository($class)
            ->count();
        if (0 === $count) {
            $io->info('No entity to update.');
        }

        return $count;
    }

    /**
     * @phpstan-param class-string $class
     *
     * @psalm-return Query
     *
     * @phpstan-return Query<EntityInterface>
     */
    private function createQuery(string $class): Query
    {
        return $this->manager->getRepository($class)
            ->createQueryBuilder('e')
            ->getQuery();
    }

    private function formatDuration(int $time): string
    {
        return Helper::formatTime(\time() - $time);
    }

    /**
     * @phpstan-return array<class-string, EntityType>
     */
    private function getEntities(): array
    {
        if ([] !== $this->entities) {
            return $this->entities;
        }

        $allMetadata = $this->manager->getMetadataFactory()->getAllMetadata();
        foreach ($allMetadata as $metadata) {
            /** @phpstan-var \ReflectionClass<\Stringable> $class */
            $class = $metadata->getReflectionClass();
            if ($class->isAbstract() || !$class->implementsInterface(EntityInterface::class)) {
                continue;
            }

            $fields = \array_filter(
                $metadata->getFieldNames(),
                static fn (string $field): bool => 'string' === $metadata->getTypeOfField($field)
            );
            if ([] === $fields) {
                continue;
            }

            \sort($fields);
            $this->entities[$metadata->getName()] = [
                'name' => $class->getShortName(),
                'fields' => $fields,
            ];
        }
        \ksort($this->entities);

        return $this->entities;
    }

    /**
     * @phpstan-param class-string $class
     */
    private function update(SymfonyStyle $io, string $class, string $field, int $total, bool $point, bool $dryRun): int
    {
        $count = 0;
        $startTime = \time();
        $query = $this->createQuery($class);
        $accessor = PropertyAccess::createPropertyAccessor();
        /** @phpstan-var EntityInterface $entity */
        foreach ($io->progressIterate($query->toIterable(), $total) as $entity) {
            /** @phpstan-var string|null $oldValue */
            $oldValue = $accessor->getValue($entity, $field);
            $newValue = $this->convert($oldValue, $point);
            if ($oldValue !== $newValue) {
                $accessor->setValue($entity, $field, $newValue);
                ++$count;
            }
        }

        if (0 === $count) {
            $io->info(
                \sprintf(
                    'No value updated of %d entities. Duration: %s.',
                    $total,
                    $this->formatDuration($startTime)
                )
            );

            return Command::SUCCESS;
        }

        $message = \sprintf(
            'Updated %d values of %d entities successfully. Duration: %s.',
            $count,
            $total,
            $this->formatDuration($startTime)
        );
        if ($dryRun) {
            $io->success($message . ' No change saved to database.');

            return Command::SUCCESS;
        }

        $this->manager->flush();
        $io->success($message);

        return Command::SUCCESS;
    }

    /**
     * @phpstan-param string|null $class
     *
     * @phpstan-return class-string|null
     */
    private function validateClass(SymfonyStyle $io, ?string $class): ?string
    {
        if (!StringUtils::isString($class)) {
            $class = $this->askClassName($io);
        }
        if (!StringUtils::isString($class)) {
            $io->error('No entity selected.');

            return null;
        }

        $entities = $this->getEntities();
        foreach ($entities as $key => $entity) {
            if (StringUtils::equalIgnoreCase($key, $class)
                || StringUtils::equalIgnoreCase($entity['name'], $class)) {
                return $key;
            }
        }
        $io->error("Unable to find the '$class' entity.");

        return null;
    }

    /**
     * @phpstan-param class-string $class
     */
    private function validateField(SymfonyStyle $io, string $class, ?string $field): ?string
    {
        if (!StringUtils::isString($field)) {
            $field = $this->askFieldName($io, $class);
        }
        if (!StringUtils::isString($field)) {
            $io->error("No field selected for the entity '$class'.");

            return null;
        }
        $entity = $this->getEntities()[$class];
        foreach ($entity['fields'] as $value) {
            if (StringUtils::equalIgnoreCase($value, $field)) {
                return $value;
            }
        }
        $io->error("Unable to find the field '$field' for the entity '$class'.");

        return null;
    }
}
