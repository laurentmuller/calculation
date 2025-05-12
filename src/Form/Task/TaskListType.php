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

namespace App\Form\Task;

use App\Entity\Task;
use App\Form\AbstractListEntityType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to display a list of tasks.
 *
 * @template-extends AbstractListEntityType<Task>
 */
class TaskListType extends AbstractListEntityType
{
    public function __construct()
    {
        parent::__construct(Task::class);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'choice_label' => 'name',
            'choice_attr' => static fn (Task $task): array => [
                'data-category-code' => $task->getCategoryCode(),
                'data-category-id' => $task->getCategoryId(),
                'data-unit' => $task->getUnit(),
            ],
            'query_builder' => fn (Options $options): QueryBuilder => $this->getSortedBuilder($options),
            'query_all' => false,
        ]);
        $resolver->setAllowedTypes('query_all', 'bool');
    }

    /**
     * @phpstan-param Options<array> $options
     *
     * @psalm-param Options $options
     *
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    private function getSortedBuilder(Options $options): QueryBuilder
    {
        /** @phpstan-var bool $all */
        $all = $options['query_all'];

        /** @phpstan-var EntityManagerInterface $manager */
        $manager = $options['em'];

        /** @phpstan-var class-string<Task> $class */
        $class = $options['class'];

        /** @phpstan-var TaskRepository $repository */
        $repository = $manager->getRepository($class); // @phpstan-ignore varTag.type

        return $repository->getSortedBuilder($all);
    }
}
