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
use Doctrine\Persistence\Mapping\ClassMetadata;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @phpstan-type EntityType = array{name: string, fields: non-empty-array<string>}
 */
#[AsCommand(name: 'app:uc-first', description: 'Convert the first character to uppercase for the defined entity and field.')]
class UcFirstCommand extends Command
{
    /** @phpstan-var array<class-string, EntityType> */
    private array $entities = [];

    public function __construct(
        private readonly SuspendEventListenerService $listener,
        private readonly EntityManagerInterface $manager
    ) {
        parent::__construct();
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
        if (!$this->validateClass($io, $class)) {
            return Command::INVALID;
        }
        if (!$this->validateField($io, $class, $field)) {
            return Command::INVALID;
        }

        $class = $this->updateClass($class);
        $field = $this->updateField($class, $field);
        $this->listener->suspendListeners(function () use ($io, $class, $field, $point, $dryRun): void {
            $startTime = \time();
            $count = $this->update($io, $class, $field, $point);
            $io->newLine();
            if (0 === $count) {
                $io->info(\sprintf('No value updated. Duration: %s.', $this->formatDuration($startTime)));
            } elseif ($dryRun) {
                $io->success(\sprintf(
                    'Updated %d values successfully. No change saved to database. Duration: %s.',
                    $count,
                    $this->formatDuration($startTime)
                ));
            } else {
                $this->manager->flush();
                $io->success(\sprintf(
                    'Updated %d values successfully. Duration: %s.',
                    $count,
                    $this->formatDuration($startTime)
                ));
            }
        });

        return Command::SUCCESS;
    }

    #[\Override]
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        /** @phpstan-var class-string|null $class */
        $class = $input->getOption('class');
        if (!StringUtils::isString($class)) {
            $class = $this->askClassName($io);
            $input->setOption('class', $class);
        }

        /** @var string|null $field */
        $field = $input->getOption('field');
        if (StringUtils::isString($class) && !StringUtils::isString($field)) {
            $input->setOption('field', $this->askFieldName($io, $class));
        }
    }

    /**
     * @return class-string|null
     */
    private function askClassName(SymfonyStyle $io): ?string
    {
        $entities = $this->getEntities();
        $question = new ChoiceQuestion('Select an entity:', \array_column($entities, 'name'));
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
        $question = new ChoiceQuestion("Select a field name for the '$name' entity:", $entry['fields'], 0);
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
            /** @var \ReflectionClass<\Stringable> $class */
            $class = $metadata->getReflectionClass();
            if ($class->isAbstract() || !$class->implementsInterface(EntityInterface::class)) {
                continue;
            }

            $fields = $this->getFields($metadata);
            if ([] === $fields) {
                continue;
            }

            $name = $metadata->getName();
            $this->entities[$name] = [
                'name' => $class->getShortName(),
                'fields' => $fields,
            ];
        }
        \ksort($this->entities);

        return $this->entities;
    }

    /**
     * @return string[]
     *
     * @phpstan-template T of object
     *
     * @phpstan-param ClassMetadata<T> $metadata
     */
    private function getFields(ClassMetadata $metadata): array
    {
        $names = \array_filter(
            $metadata->getFieldNames(),
            fn (string $field): bool => 'string' === $metadata->getTypeOfField($field)
        );
        \sort($names);

        return $names;
    }

    /**
     * @phpstan-param class-string $class
     */
    private function update(SymfonyStyle $io, string $class, string $field, bool $point): int
    {
        $entities = $this->manager->getRepository($class)->findAll();
        if ([] === $entities) {
            return 0;
        }

        $count = 0;
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($io->progressIterate($entities) as $entity) {
            /** @phpstan-var string|null $oldValue */
            $oldValue = $accessor->getValue($entity, $field);
            $newValue = $this->convert($oldValue, $point);
            if ($oldValue !== $newValue) {
                $accessor->setValue($entity, $field, $newValue);
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @phpstan-param class-string $class
     *
     * @phpstan-return class-string
     */
    private function updateClass(string $class): string
    {
        $entities = $this->getEntities();
        foreach ($entities as $key => $value) {
            if (StringUtils::equalIgnoreCase($key, $class)
                || StringUtils::equalIgnoreCase($value['name'], $class)) {
                return $key;
            }
        }

        return $class;
    }

    /**
     * @phpstan-param class-string $class
     */
    private function updateField(string $class, string $field): string
    {
        $entity = $this->getEntities()[$class];
        foreach ($entity['fields'] as $value) {
            if (StringUtils::equalIgnoreCase($value, $field)) {
                return $value;
            }
        }

        return $field;
    }

    /**
     * @phpstan-assert-if-true class-string $class
     */
    private function validateClass(SymfonyStyle $io, ?string $class): bool
    {
        if (!StringUtils::isString($class)) {
            $io->error('No entity selected.');

            return false;
        }

        $entities = $this->getEntities();
        foreach ($entities as $key => $entity) {
            if (StringUtils::equalIgnoreCase($key, $class)
                || StringUtils::equalIgnoreCase($entity['name'], $class)) {
                return true;
            }
        }

        $io->error("Unable to find the entity '$class'.");

        return false;
    }

    /**
     * @phpstan-param class-string $class
     *
     * @phpstan-assert-if-true non-empty-string $field
     */
    private function validateField(SymfonyStyle $io, string $class, ?string $field): bool
    {
        if (!StringUtils::isString($field)) {
            $io->error("No field selected for the entity '$class'.");

            return false;
        }

        $entity = $this->getEntities()[$class];
        foreach ($entity['fields'] as $value) {
            if (StringUtils::equalIgnoreCase($value, $field)) {
                return true;
            }
        }

        $io->error("Unable to find field '$field' for the entity '$class'.");

        return false;
    }
}
