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

namespace App\Repository;

use App\Entity\TaskItem;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for task item entity.
 *
 * @template-extends AbstractRepository<TaskItem>
 */
class TaskItemRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskItem::class);
    }
}
