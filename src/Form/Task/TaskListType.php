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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to display a list of tasks.
 *
 * @extends AbstractListEntityType<Task>
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
            'query_builder' => $this->getSortedBuilder(...),
            'query_all' => false,
        ]);
        $resolver->setAllowedTypes('query_all', 'bool');
    }

    /**
     * @phpstan-param Options<array> $options
     *
     * @psalm-param Options $options
     */
    private function getSortedBuilder(Options $options): QueryBuilder
    {
        /** @var bool $all */
        $all = $options['query_all'];

        /** @var EntityManagerInterface $manager */
        $manager = $options['em'];

        return $manager->getRepository(Task::class)
            ->getSortedBuilder($all);
    }
}
