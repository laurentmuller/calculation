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

namespace App\Tests\Controller;

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Product;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\CalculationRepository;

/**
 * Unit test for {@link App\Controller\BootstrapTableController} class.
 *
 * @author Laurent Muller
 */
class BootstrapTableControllerTest extends AbstractControllerTest
{
    private static ?Calculation $calculation = null;
    private static ?Category $category = null;
    private static ?Group $group = null;
    private static ?Product $product = null;
    private static ?CalculationState $state = null;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var LoggerInterface $logger */
        $logger = self::$container->get(LoggerInterface::class);
        $logger->info('BootstrapTableControllerTest: A message for testing purposes.');
    }

    public function getRoutes(): array
    {
        return [
            ['/table/below', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/table/below', self::ROLE_ADMIN],
            ['/table/below', self::ROLE_SUPER_ADMIN],

            ['/table/calculation', self::ROLE_USER],
            ['/table/calculation', self::ROLE_ADMIN],
            ['/table/calculation', self::ROLE_SUPER_ADMIN],

            ['/table/calculationstate', self::ROLE_USER],
            ['/table/calculationstate', self::ROLE_ADMIN],
            ['/table/calculationstate', self::ROLE_SUPER_ADMIN],

            ['/table/category', self::ROLE_USER],
            ['/table/category', self::ROLE_ADMIN],
            ['/table/category', self::ROLE_SUPER_ADMIN],

            ['/table/customer', self::ROLE_USER],
            ['/table/customer', self::ROLE_ADMIN],
            ['/table/customer', self::ROLE_SUPER_ADMIN],

            ['/table/duplicate', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/table/duplicate', self::ROLE_ADMIN],
            ['/table/duplicate', self::ROLE_SUPER_ADMIN],

            ['/table/empty', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/table/empty', self::ROLE_ADMIN],
            ['/table/empty', self::ROLE_SUPER_ADMIN],

            ['/table/globalmargin', self::ROLE_USER],
            ['/table/globalmargin', self::ROLE_ADMIN],
            ['/table/globalmargin', self::ROLE_SUPER_ADMIN],

            ['/table/group', self::ROLE_USER],
            ['/table/group', self::ROLE_ADMIN],
            ['/table/group', self::ROLE_SUPER_ADMIN],

            ['/table/log', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/table/log', self::ROLE_ADMIN],
            ['/table/log', self::ROLE_SUPER_ADMIN],

            ['/table/product', self::ROLE_USER],
            ['/table/product', self::ROLE_ADMIN],
            ['/table/product', self::ROLE_SUPER_ADMIN],

            ['/table/search', self::ROLE_USER],
            ['/table/search', self::ROLE_ADMIN],
            ['/table/search', self::ROLE_SUPER_ADMIN],

            ['/table/task', self::ROLE_USER],
            ['/table/task', self::ROLE_ADMIN],
            ['/table/task', self::ROLE_SUPER_ADMIN],

            ['/table/user', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/table/user', self::ROLE_ADMIN],
            ['/table/user', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function addEntities(): void
    {
        if (null === self::$state) {
            self::$state = new CalculationState();
            self::$state->setCode('Test State');
            $this->addEntity(self::$state);
        }

        if (null === self::$group) {
            self::$group = new Group();
            self::$group->setCode('Test Group');

            $this->addEntity(self::$group);
        }

        if (null === self::$category) {
            self::$category = new Category();
            self::$category->setCode('Test Category')
                ->setGroup(self::$group);
            $this->addEntity(self::$category);
        }

        if (null === self::$product) {
            self::$product = new Product();
            self::$product->setDescription('Test Product')
                ->setCategory(self::$category)
                ->setPrice(1.0);
            $this->addEntity(self::$product);
        }

        if (null === self::$calculation) {
            self::$calculation = new Calculation();
            self::$calculation->setCustomer('Test Customer')
                ->setDescription('Test Description')
                ->setState(self::$state)
                ->addProduct(self::$product, 0.0)
                ->addProduct(self::$product, 1.0)
                ->setItemsTotal(1.0)
                ->setGlobalMargin(1.0)
                ->setOverallTotal(2.0);
            $this->addEntity(self::$calculation);

            $this->doEcho('EmptyItems', self::$calculation->hasEmptyItems() ? 'true' : 'false');
            $this->doEcho('DuplicateItems', self::$calculation->hasDuplicateItems() ? 'true' : 'false');

            /** @var CalculationRepository $repository */
            $repository = self::$container->get(CalculationRepository::class);
            $this->doEcho('CountEmptyItems', $repository->countEmptyItems());
            $this->doEcho('CountDuplicateItems', $repository->countDuplicateItems());
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function deleteEntities(): void
    {
        self::$calculation = $this->deleteEntity(self::$calculation);
        self::$product = $this->deleteEntity(self::$product);
        self::$category = $this->deleteEntity(self::$category);
        self::$group = $this->deleteEntity(self::$group);
        self::$state = $this->deleteEntity(self::$state);
    }
}
