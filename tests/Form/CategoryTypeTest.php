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

use App\Entity\Category;
use App\Entity\Group;
use App\Form\Category\CategoryType;
use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\PreloadedExtension;

/**
 * @extends AbstractEntityTypeTestCase<Category, CategoryType>
 */
#[\PHPUnit\Framework\Attributes\CoversClass(CategoryType::class)]
class CategoryTypeTest extends AbstractEntityTypeTestCase
{
    private static ?Group $group = null;

    protected function getData(): array
    {
        return [
            'code' => 'code',
            'description' => 'description',
        ];
    }

    protected function getEntityClass(): string
    {
        return Category::class;
    }

    /**
     * @throws Exception
     */
    protected function getExtensions(): array
    {
        /** @psalm-var \Symfony\Component\Form\FormExtensionInterface[] $extensions */
        $extensions = parent::getExtensions();

        $registry = $this->getRegistry();
        $type = new EntityType($registry);
        $extensions[] = new PreloadedExtension([$type], []);

        return $extensions;
    }

    protected function getFormTypeClass(): string
    {
        return CategoryType::class;
    }

    private static function getGroup(): Group
    {
        if (!self::$group instanceof Group) {
            self::$group = new Group();
            self::$group->setCode('group');
            $property = new \ReflectionProperty(Group::class, 'id');
            $property->setValue(self::$group, 1);
        }

        return self::$group;
    }

    /**
     * @throws Exception
     */
    private function getRegistry(): ManagerRegistry
    {
        $manager = $this->createMock(EntityManager::class);
        $manager->method('getClassMetadata')
            ->willReturn(new ClassMetadata(Group::class));

        $query = $this->createMock(AbstractQuery::class);
        $query->method('execute')
            ->willReturn([self::getGroup()]);

        $query->method('getSQL')
            ->willReturn('FakeSQL');

        $builder = $this->createMock(QueryBuilder::class);
        $builder->method('getParameters')
            ->willReturn(new ArrayCollection());
        $builder->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(GroupRepository::class);
        $repository->method('getSortedBuilder')
            ->willReturn($builder);

        $manager->method('getRepository')->willReturn($repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')
            ->willReturn($manager);

        return $registry;
    }
}
