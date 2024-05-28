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
use App\Tests\Entity\IdTrait;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\PreloadedExtension;

/**
 * @extends AbstractEntityTypeTestCase<Category, CategoryType>
 */
#[CoversClass(CategoryType::class)]
class CategoryTypeTest extends AbstractEntityTypeTestCase
{
    use IdTrait;

    private static ?Group $group = null;

    protected function getData(): array
    {
        return [
            'code' => 'code',
            'description' => 'description',
            'group' => null,
        ];
    }

    protected function getEntityClass(): string
    {
        return Category::class;
    }

    /**
     * @throws Exception|\ReflectionException
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

    /**
     * @throws \ReflectionException
     */
    private function getGroup(): Group
    {
        if (!self::$group instanceof Group) {
            self::$group = new Group();
            self::$group->setCode('group');

            return self::setId(self::$group);
        }

        return self::$group;
    }

    /**
     * @throws Exception|\ReflectionException
     */
    private function getRegistry(): MockObject&ManagerRegistry
    {
        $query = $this->createQuery();
        $builder = $this->createQueryBuilder($query);
        $manager = $this->createEntityManager();
        $repository = $this->createRepository(GroupRepository::class);
        $registry = $this->createRegistry($manager);

        $query->expects(self::any())
            ->method('execute')
            ->willReturn([$this->getGroup()]);

        $repository->expects(self::any())
            ->method('getSortedBuilder')
            ->willReturn($builder);

        $manager->expects(self::any())
            ->method('getRepository')
            ->willReturn($repository);

        return $registry;
    }
}
