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

use App\Controller\UpdateEntityController;
use App\Entity\Customer;
use App\Entity\Product;
use App\Tests\EntityTrait\CalculationStateTrait;
use App\Tests\EntityTrait\CategoryTrait;
use App\Tests\EntityTrait\GroupTrait;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(UpdateEntityController::class)]
class UpdateEntityControllerTest extends AbstractControllerTestCase
{
    use CalculationStateTrait;
    use CategoryTrait;
    use GroupTrait;

    private ?Customer $customer = null;
    /** @var Product[]|null */
    private ?array $products = null;

    public static function getRoutes(): array
    {
        return [
            ['/update', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/update', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/update', self::ROLE_SUPER_ADMIN],

            ['/update/calculation', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/update/calculation', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/update/calculation', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND],

            ['/update/customer', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/update/customer', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/update/customer', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND],
        ];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $this->getCalculationState();
        $group = $this->getGroup();
        $category = $this->getCategory($group);

        if (!$this->customer instanceof Customer) {
            $this->customer = new Customer();
            $this->customer->setCompany('Test Company');
            $this->addEntity($this->customer);
        }

        if (null === $this->products) {
            for ($i = 0; $i < 15; ++$i) {
                $product = new Product();
                $product->setDescription("Test Product $i")
                    ->setCategory($category);
                $this->addEntity($product);
                $this->products[] = $product;
            }
        }
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        if (null !== $this->products) {
            foreach ($this->products as $product) {
                $this->deleteEntity($product);
            }
            $this->products = null;
        }
        $this->deleteCategory();
        $this->deleteGroup();
        $this->deleteCalculationState();
        $this->customer = $this->deleteEntity($this->customer);
    }
}
