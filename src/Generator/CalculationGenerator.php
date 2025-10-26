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
use App\Faker\ProductProvider;
use App\Interfaces\EntityInterface;
use App\Service\CalculationUpdateService;
use App\Service\FakerService;
use App\Utils\DateUtils;
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
        private readonly CalculationUpdateService $service
    ) {
        parent::__construct($manager, $fakerService);
    }

    #[\Override]
    protected function createEntities(int $count, bool $simulate, Generator $generator): array
    {
        $entities = [];
        [$min, $max] = $this->getMinMax($generator);
        for ($i = 0; $i < $count; ++$i) {
            $entities[] = $this->createEntity($min, $max, $generator);
        }

        return $entities;
    }

    #[\Override]
    protected function getCountMessage(int $count): string
    {
        return $this->trans('counters.calculations_generate', ['count' => $count]);
    }

    #[\Override]
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

    private function createEntity(int $min, int $max, Generator $generator): Calculation
    {
        $date = DateUtils::toDatePoint($generator->dateTimeBetween('today', 'last day of next month'));
        $entity = $this->generateEntity($min, $max, $generator)
            ->setDescription($generator->catchPhrase())
            ->setUserMargin($generator->randomFloat(2, 0, 0.1))
            ->setCustomer($generator->name())
            ->setState($generator->state())
            ->setDate($date);
        $this->service->updateCalculation($entity);

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
                $entity->findOrCreateCategory($category)->addItem($item);
            }
        }

        return $entity;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function getMinMax(Generator $generator): array
    {
        $provider = $generator->getProvider(ProductProvider::class);
        $count = $provider instanceof ProductProvider ? \count($provider) : 0;

        return [\min(5, $count), \min(15, $count)];
    }
}
