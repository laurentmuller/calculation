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
use App\Entity\Product;
use App\Form\Product\ProductType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Test for the {@link App\Form\Product\ProductType} class.
 *
 * @author Laurent Muller
 */
class ProductTypeTest extends TypeTestCase
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
        $this->manager = DoctrineTestHelper::createTestEntityManager();
        $this->registry = $this->createRegistryMock('default');

        parent::setUp();

        $schemaTool = new SchemaTool($this->manager);
        $classes = [
            $this->manager->getClassMetadata(Product::class),
            $this->manager->getClassMetadata(Category::class),
            $this->manager->getClassMetadata(Group::class),
        ];

        try {
            $schemaTool->dropSchema($classes);
        } catch (\Exception $e) {
        }

        try {
            $schemaTool->createSchema($classes);
        } catch (\Exception $e) {
        }
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

    public function checkRepository(): void
    {
        $category = new Category();
        $category->setCode('code');

        // Now, mock the repository so it returns the mock of the employee
        $repository = $this->createMock(ObjectRepository::class);

        $repository->expects($this->any())
            ->method('find')
            ->willReturn($category);

        $objectManager = $this->createMock(ObjectManager::class);

        $objectManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);
    }

    public function testSubmitValidData(): void
    {
        $category = new Category();
        $category->setCode('category');

        $group = new Group();
        $group->setCode('group');
        $group->addCategory($category);

        $data = [
            'category' => $category,
            'description' => 'description',
            'price' => 1.0,
            'supplier' => 'supplier',
            'unit' => 'unit',
        ];

        // create model and form
        $model = new Product();
        $form = $this->factory->create(ProductType::class, $model);

        // populate form data
        $expected = new Product();
        $expected->setCategory($data['category'])
            ->setDescription($data['description'])
            ->setPrice($data['price'])
            ->setSupplier($data['supplier'])
            ->setUnit($data['unit']);

        // submit the data to the form directly
        $form->submit($data);

        // check form
        $this->assertTrue($form->isSynchronized());

        // check data
        $this->assertEquals($expected, $model);

        // check view
        // $view = $form->createView();
        // foreach (array_keys($data) as $key) {
        //  static::assertArrayHasKey($key, $view->children);
        // }
    }

    protected function createRegistryMock(string $name): ManagerRegistry
    {
        $registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();
        $registry->expects($this->any())
            ->method('getManager')
            ->with($this->equalTo($name))
            ->willReturn($this->manager);

        return $registry;
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
}
