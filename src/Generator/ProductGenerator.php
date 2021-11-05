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

namespace App\Generator;

use App\Entity\Product;
use App\Faker\Generator;
use App\Util\FormatUtils;
use App\Util\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class to generate products.
 *
 * @author Laurent Muller
 */
class ProductGenerator extends AbstractEntityGenerator
{
    /**
     * {@inheritDoc}
     */
    protected function generateEntities(int $count, bool $simulate, EntityManagerInterface $manager, Generator $generator): JsonResponse
    {
        try {
            $products = [];
            for ($i = 0; $i < $count; ++$i) {
                $product = new Product();
                $description = $this->getDescription($generator);
                $product->setDescription($description)
                    ->setPrice($generator->randomFloat(2, 1, 50))
                    ->setSupplier($generator->productSupplier())
                    ->setUnit($generator->productUnit())
                    ->setCategory($generator->category());

                // save
                if (!$simulate) {
                    $manager->persist($product);
                }

                // add
                $products[] = $product;
            }

            // save
            if (!$simulate) {
                $manager->flush();
            }

            // serialize
            $items = \array_map(static function (Product $p): array {
                return [
                    'id' => $p->getId(),
                    'group' => $p->getGroupCode(),
                    'category' => $p->getCategoryCode(),
                    'description' => $p->getDescription(),
                    'price' => FormatUtils::formatAmount($p->getPrice()),
                    'unit' => $p->getUnit(),
                    'supplier' => $p->getSupplier(),
                ];
            }, $products);

            return new JsonResponse([
                'result' => true,
                'items' => $items,
                'count' => \count($items),
                'simulate' => $simulate,
                'message' => $this->trans('counters.products_generate', ['count' => $count]),
            ]);
        } catch (\Exception $e) {
            $message = $this->trans('generate.error.failed');
            $context = Utils::getExceptionContext($e);
            $this->logError($message, $context);

            return new JsonResponse([
                'result' => false,
                'message' => $message,
                'exception' => $context,
            ]);
        }
    }

    /**
     * Gets a product's description.
     */
    private function getDescription(Generator $generator): string
    {
        $try = 0;
        $description = $generator->productName();
        while ($try < 10 && $generator->productExist($description)) {
            $description = $generator->productName();
            ++$try;
        }

        return $description;
    }
}
