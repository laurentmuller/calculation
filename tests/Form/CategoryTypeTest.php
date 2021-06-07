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

namespace App\Tests\Form;

use App\Entity\Category;
use App\Entity\Group;
use App\Form\Category\CategoryType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;

/**
 * Test for the {@link App\Form\Category\CategoryType} class.
 *
 * @see https://github.com/symfony/doctrine-bridge/blob/5.3/Tests/Form/Type/EntityTypeTest.php
 *
 * @author Laurent Muller
 */
class CategoryTypeTest extends AbstractEntityTypeTestCase
{
    /**
     * @var EntityManager|null
     */
    private $manager = null;

    /**
     * @var ManagerRegistry|null
     */
    private $registry = null;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $group = new Group();
        $group->setCode('code');
        $this->manager = DoctrineTestHelper::createTestEntityManager();

//        $this->markTestSkipped('The App\Entity\Group can not be found.');

//         $query = $this->getMockBuilder(\QueryMock::class)
//             ->setMethods(['setParameter', 'getResult', 'getSql', '_doExecute'])
//             ->getMock();

//         $query->method('getResult')
//             ->willReturn([$group]);

        // //         $query->expects($this->once())
        // //             ->method('setParameter')
        // //             ->with('ORMQueryBuilderLoader_getEntitiesByIds_id', [1, 2], $expectedType)
        // //             ->willReturn($query);

//         $qb = $this->getMockBuilder(\Doctrine\ORM\QueryBuilder::class)
//             ->setConstructorArgs([$this->em])
//             ->setMethods(['getQuery'])
//             ->getMock();

//         $qb->expects($this->once())
//             ->method('getQuery')
//             ->willReturn($query);

        $this->registry = $this->createRegistryMock('default', $this->manager);

        parent::setUp();

        $schemaTool = new SchemaTool($this->manager);
        $schemaTool->createSchema([
            $this->manager->getClassMetadata(Group::class),
            $this->manager->getClassMetadata(Category::class),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->manager = null;
        $this->registry = null;
    }

    protected function createRegistryMock(string $name, EntityManager $manager): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManager')
            ->with($this->equalTo($name))
            ->willReturn($manager);

//         $registry->expects($this->any())
//             ->method('getManagerForClass')
//             ->with(Group::class)
//             ->willReturn($manager);

        return $registry;
    }

    /**
     * {@inheritDoc}
     */
    protected function getData(): array
    {
        return [
            'code' => 'code',
            'description' => 'description',
            //'group' => $this->group,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntityClass(): string
    {
        return Category::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return \array_merge(parent::getExtensions(), [
            new DoctrineOrmExtension($this->registry),
        ]);
    }

    protected function getFormTypeClass(): string
    {
        return CategoryType::class;
    }
}
