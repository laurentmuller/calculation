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

namespace App\Tests\Controller;

use App\Controller\GeneratorController;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Product;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(GeneratorController::class)]
class GeneratorControllerTestCase extends AbstractControllerTestCase
{
    private static ?Category $category = null;
    private static ?Group $group = null;
    private static ?array $products = null;
    private static ?CalculationState $state = null;

    public static function getRoutes(): array
    {
        return [
            ['/generate', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/generate', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/generate', self::ROLE_SUPER_ADMIN],

            ['/generate/calculation', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/generate/calculation', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/generate/calculation', self::ROLE_SUPER_ADMIN],

            ['/generate/customer', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/generate/customer', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/generate/customer', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        if (!self::$state instanceof CalculationState) {
            self::$state = new CalculationState();
            self::$state->setCode('Test Code');
            $this->addEntity(self::$state);
        }

        if (!self::$group instanceof Group) {
            self::$group = new Group();
            self::$group->setCode('Test Group');
            $this->addEntity(self::$group);
        }

        if (!self::$category instanceof Category) {
            self::$category = new Category();
            self::$category->setCode('Test Category')
                ->setGroup(self::$group);
            $this->addEntity(self::$category);
        }

        if (null === self::$products) {
            for ($i = 0; $i < 15; ++$i) {
                $product = new Product();
                $product->setDescription("Test Product $i")
                    ->setCategory(self::$category);
                $this->addEntity($product);
                self::$products[] = $product;
            }
        }
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        foreach (self::$products as $product) {
            $this->deleteEntity($product);
        }
        self::$products = null;

        self::$category = $this->deleteEntity(self::$category);
        self::$group = $this->deleteEntity(self::$group);
        self::$state = $this->deleteEntity(self::$state);
    }
}
