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

namespace App\Generator;

use App\Entity\Calculation;
use App\Entity\CalculationItem;
use App\Entity\Category;
use App\Faker\Generator;
use App\Interfaces\EntityInterface;
use App\Service\CalculationService;
use App\Service\FakerService;
use App\Utils\FormatUtils;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class to generate calculations.
 *
 * @extends AbstractEntityGenerator<Calculation>
 */
class CalculationGenerator extends AbstractEntityGenerator
{
    public function __construct(
        EntityManagerInterface $manager,
        FakerService $fakerService,
        private readonly CalculationService $service
    ) {
        parent::__construct($manager, $fakerService);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function createEntities(int $count, bool $simulate, Generator $generator): array
    {
        $entities = [];
        [$min, $max] = $this->getMinMax($generator);
        for ($i = 0; $i < $count; ++$i) {
            $entities[] = $this->createEntity($min, $max, $generator);
        }

        return $entities;
    }

    protected function getCountMessage(int $count): string
    {
        return $this->trans('counters.calculations_generate', ['count' => $count]);
    }

    protected function mapEntity(EntityInterface $entity): array
    {
        return [
            'date' => $entity->getFormattedDate(),
            'state' => $entity->getStateCode(),
            'customer' => $entity->getCustomer(),
            'description' => $entity->getDescription(),
            'margin' => FormatUtils::formatPercent($entity->getOverallMargin()),
            'total' => FormatUtils::formatAmount($entity->getOverallTotal()),
            'color' => $entity->getStateColor(),
        ];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function createEntity(int $min, int $max, Generator $generator): Calculation
    {
        $date = $generator->dateTimeBetween('today', 'last day of next month');
        $entity = $this->generateEntity($min, $max, $generator)
            ->setDescription($generator->catchPhrase())
            ->setUserMargin($generator->randomFloat(2, 0, 0.1))
            ->setCustomer($generator->name())
            ->setState($generator->state())
            ->setDate($date);
        $this->service->updateTotal($entity);

        return $entity;
    }

    private function generateEntity(int $min, int $max, Generator $generator): Calculation
    {
        $entity = new Calculation();
        $products = $generator->products($generator->numberBetween($min, $max));
        foreach ($products as $product) {
            $item = CalculationItem::create($product)->setQuantity($generator->numberBetween(1, 10));
            if ($item->isEmptyPrice()) {
                $item->setPrice($generator->randomFloat(2, 1, 10));
            }
            $category = $product->getCategory();
            if ($category instanceof Category) {
                $entity->findCategory($category)->addItem($item);
            }
        }

        return $entity;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function getMinMax(Generator $generator): array
    {
        $productsCount = $generator->productsCount();

        return [\min(5, $productsCount), \min(15, $productsCount)];
    }
}
