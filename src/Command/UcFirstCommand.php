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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Command to set a first character uppercase to fields.
 *
 * @psalm-type EntitiesType = array<class-string, array{name: string, fields: non-empty-array<string>}>
 */
#[AsCommand(name: 'app:uc-first', description: 'Set a first character uppercase to defined fields.')]
class UcFirstCommand extends Command
{
    private const OPTION_CLASS = 'class';
    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_END_POINT = 'point';
    private const OPTION_FIELD = 'field';

    /** @psalm-var  EntitiesType */
    private array $entities = [];

    public function __construct(
        private readonly SuspendEventListenerService $listener,
        private readonly EntityManagerInterface $manager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(self::OPTION_CLASS, 'c', InputOption::VALUE_OPTIONAL, 'The entity class to update.');
        $this->addOption(self::OPTION_FIELD, 'f', InputOption::VALUE_OPTIONAL, 'The field name to update.');
        $this->addOption(self::OPTION_END_POINT, 'p', InputOption::VALUE_NONE, 'Add a point (".") at the end of the value.');
        $this->addOption(self::OPTION_DRY_RUN, 'd', InputOption::VALUE_NONE, 'Simulate update without flush change to the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $className = $this->getClassName($io);
        if (null === $className) {
            return Command::INVALID;
        }

        $fieldName = $this->getFieldName($io, $className);
        if (null === $fieldName) {
            return Command::INVALID;
        }

        $this->listener->suspendListeners(function () use ($io, $className, $fieldName): void {
            $endPoint = $io->getBoolOption(self::OPTION_END_POINT);
            $dryRun = $io->getBoolOption(self::OPTION_DRY_RUN);
            $count = $this->update($io, $className, $fieldName, $endPoint);
            $io->newLine();
            if (0 === $count) {
                $io->info('No value updated.');
            } elseif ($dryRun) {
                $io->success(\sprintf('Updated %d values successfully. No change saved to database.', $count));
            } else {
                $this->manager->flush();
                $io->success(\sprintf('Updated %d values successfully.', $count));
            }
        });

        return Command::SUCCESS;
    }

    /**
     * @psalm-return class-string|null
     */
    private function askClassName(SymfonyStyle $io): ?string
    {
        if (!$this->isInteractive($io)) {
            return null;
        }

        $entities = $this->getEntities();
        $question = new ChoiceQuestion('Select an entity:', \array_column($entities, 'name'));
        $question->setMaxAttempts(1)
            ->setErrorMessage('No entity selected.');

        /** @psalm-var ?string $entity */
        $entity = $io->askQuestion($question);
        if (!\is_string($entity)) {
            return null;
        }

        foreach ($entities as $class => $entry) {
            if ($entry['name'] === $entity) {
                return $class;
            }
        }

        return null;
    }

    /**
     * @psalm-param class-string $className
     */
    private function askFieldName(SymfonyStyle $io, string $className): ?string
    {
        if (!$this->isInteractive($io)) {
            return null;
        }

        $entry = $this->getEntities()[$className];
        $name = $entry['name'];
        $question = new ChoiceQuestion("Select a field name for the '$name' entity:", $entry['fields'], 0);
        $question->setMaxAttempts(1)
            ->setErrorMessage("No field selected for the '$name' entity.");

        /** @psalm-var ?string $fieldName */
        $fieldName = $io->askQuestion($question);
        if (!\is_string($fieldName)) {
            return null;
        }

        return $fieldName;
    }

    private function convert(?string $str, bool $endPoint): ?string
    {
        if (null === $str || '' === $str) {
            return $str;
        }
        $str = \ucfirst($str);
        if ($endPoint && !\str_ends_with($str, '.')) {
            $str .= '.';
        }

        return $str;
    }

    /**
     * @psalm-return class-string|null
     */
    private function getClassName(SymfonyStyle $io): ?string
    {
        /** @psalm-var class-string|null $className */
        $className = $io->getOption(self::OPTION_CLASS);
        if (null === $className) {
            return $this->askClassName($io);
        }

        $entities = $this->getEntities();
        foreach ($entities as $key => $value) {
            if (StringUtils::equalIgnoreCase($key, $className)
                || StringUtils::equalIgnoreCase($value['name'], $className)) {
                return $key;
            }
        }

        $io->error("Unable to find the entity '$className'.");

        return null;
    }

    /**
     * @psalm-return EntitiesType
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
     * @psalm-param class-string $className
     */
    private function getFieldName(SymfonyStyle $io, string $className): ?string
    {
        $fieldName = $io->getOption(self::OPTION_FIELD);
        if (!\is_string($fieldName)) {
            return $this->askFieldName($io, $className);
        }

        $entity = $this->getEntities()[$className];
        foreach ($entity['fields'] as $field) {
            if (StringUtils::equalIgnoreCase($field, $fieldName)) {
                return $field;
            }
        }

        $name = $entity['name'];
        $io->error("Unable to find the field '$fieldName' for the '$name' entity.");

        return null;
    }

    /**
     * @psalm-return string[]
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

    private function isInteractive(SymfonyStyle $io): bool
    {
        if ($io->isInteractive()) {
            $io->error('Unable to ask question in interactive mode.');

            return true;
        }

        return false;
    }

    /**
     * @psalm-param class-string $className
     */
    private function update(SymfonyStyle $io, string $className, string $fieldName, bool $endPoint): int
    {
        $entities = $this->manager->getRepository($className)->findAll();
        if ([] === $entities) {
            return 0;
        }

        $count = 0;
        $accessor = PropertyAccess::createPropertyAccessor();
        $io->progressStart(\count($entities));
        foreach ($entities as $entity) {
            /** @psalm-var string|null $oldValue */
            $oldValue = $accessor->getValue($entity, $fieldName);
            $newValue = $this->convert($oldValue, $endPoint);
            if ($oldValue !== $newValue) {
                $accessor->setValue($entity, $fieldName, $newValue);
                ++$count;
            }
            $io->progressAdvance();
        }

        return $count;
    }
}
