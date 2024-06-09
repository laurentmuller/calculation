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

namespace App\Tests\Faker;

use App\Entity\Category;
use App\Faker\CategoryProvider;
use App\Faker\EntityProvider;
use App\Faker\Factory;
use App\Repository\CategoryRepository;
use App\Utils\FormatUtils;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(EntityProvider::class)]
#[CoversClass(CategoryProvider::class)]
class CategoryProviderTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testWithEntity(): void
    {
        $entity = new Category();
        $entity->setCode('code');
        $provider = $this->createProvider($entity);

        $actual = $provider->categoriesCount();
        self::assertSame(1, $actual);

        $actual = $provider->category();
        self::assertSame($entity, $actual);
    }

    /**
     * @throws Exception
     */
    public function testWithoutEntity(): void
    {
        $provider = $this->createProvider();

        $actual = $provider->categoriesCount();
        self::assertSame(0, $actual);

        $actual = $provider->category();
        self::assertNull($actual);
    }

    /**
     * @throws Exception
     */
    private function createProvider(?Category $entity = null): CategoryProvider
    {
        $entities = $entity instanceof Category ? [$entity] : [];
        $repository = $this->createMock(CategoryRepository::class);
        $repository->expects(self::any())
            ->method('findBy')
            ->willReturn($entities);

        $repository->expects(self::any())
            ->method('findAll')
            ->willReturn($entities);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::any())
            ->method('getRepository')
            ->willReturn($repository);

        $generator = Factory::create(FormatUtils::DEFAULT_LOCALE);

        return new CategoryProvider($generator, $manager);
    }
}
