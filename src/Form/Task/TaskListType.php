<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Form\Task;

use App\Entity\Task;
use App\Form\AbstractListEntityType;
use App\Repository\TaskRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to display a list of tasks.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractListEntityType<Task>
 */
class TaskListType extends AbstractListEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Task::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'choice_label' => 'name',
            'choice_attr' => function (Task $task): array {
                return [
                    'data-category-id' => $task->getCategoryId(),
                    'data-category-code' => $task->getCategoryCode(),
                    'data-unit' => $task->getUnit(),
                ];
            },
            'query_builder' => function (TaskRepository $repository): QueryBuilder {
                return $repository->getSortedBuilder(false);
            },
        ]);
    }
}
