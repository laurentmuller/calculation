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

namespace App\Tests\Form;

use App\Entity\Group;
use App\Repository\GroupRepository;
use App\Tests\Entity\IdTrait;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @psalm-require-extends TestCase
 */
trait GroupTrait
{
    use IdTrait;
    use ManagerRegistryTrait;

    private ?Group $group = null;

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getEntityType(): EntityType
    {
        return new EntityType($this->getRegistry());
    }

    /**
     * @throws \ReflectionException
     */
    protected function getGroup(): Group
    {
        if (!$this->group instanceof Group) {
            $this->group = new Group();
            $this->group->setCode('group');

            return self::setId($this->group);
        }

        return $this->group;
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getRegistry(): MockObject&ManagerRegistry
    {
        $query = $this->createQuery([$this->getGroup()]);
        $builder = $this->createQueryBuilder($query);
        $manager = $this->createEntityManager(Group::class);
        $repository = $this->createRepository(GroupRepository::class);
        $registry = $this->createRegistry($manager);

        $repository->method('getSortedBuilder')
            ->willReturn($builder);

        $manager->method('getRepository')
            ->willReturn($repository);

        return $registry;
    }
}
