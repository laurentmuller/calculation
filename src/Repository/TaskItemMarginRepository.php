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

namespace App\Repository;

use App\Entity\TaskItemMargin;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for task item margin entity.
 *
 * @method TaskItemMargin|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaskItemMargin|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaskItemMargin[]    findAll()
 * @method TaskItemMargin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\TaskItemMargin
 * @template-extends AbstractRepository<TaskItemMargin>
 */
class TaskItemMarginRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskItemMargin::class);
    }
}
