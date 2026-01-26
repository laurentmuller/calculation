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

namespace App\Faker;

use App\Entity\Product;
use App\Repository\ProductRepository;

/**
 * Product provider.
 *
 * @extends EntityProvider<Product>
 */
class ProductProvider extends EntityProvider
{
    private const array ADJECTIVE = [
        'Petit',
        'Ergonomique',
        'Rustique',
        'Intelligent',
        'Magnifique',
        'Incroyable',
        'Fantastique',
        'Pratique',
        'Elégant',
        'Génial',
        'Enorme',
        'Médiocre',
        'Synergique',
        'Robuste',
        'Léger',
        'Aérodynamique',
        'Durable'];

    private const array MATERIAL = [
        'acier',
        'bois',
        'béton',
        'plastique',
        'coton',
        'granit',
        'caoutchouc',
        'cuir',
        'soie',
        'laine',
        'lin',
        'marbre',
        'fer',
        'bronze',
        'cuivre',
        'aluminium',
        'papier'];

    private const array PRODUCT = [
        'tabouret',
        'camion',
        'ordinateur',
        'gants',
        'pantalon',
        'chemisier',
        'tabouret',
        'chausse-pied',
        'chapeau',
        'vase',
        'couteau',
        'récipient',
        'manteau',
        'carnet',
        'clavier',
        'sac',
        'banc',
        'stylo',
        'boîtier',
        'portefeuille'];

    public function __construct(Generator $generator, ProductRepository $repository)
    {
        parent::__construct($generator, $repository);
    }

    /**
     * Gets a random product.
     */
    public function product(): ?Product
    {
        return $this->entity();
    }

    /**
     * Returns if the given product's description exists.
     */
    public function productExist(string $description): bool
    {
        return $this->repository->findOneBy(['description' => $description]) instanceof Product;
    }

    /**
     * Gets a random product's name.
     */
    public function productName(): string
    {
        $adjective = (string) static::randomElement(self::ADJECTIVE);
        $product = (string) static::randomElement(self::PRODUCT);
        $material = (string) static::randomElement(self::MATERIAL);

        return \sprintf('%s %s en %s', $adjective, $product, $material);
    }

    /**
     * Gets random products. The products are sorted by category code and description.
     *
     * @return Product[]
     */
    public function products(int $count = 1, bool $allowDuplicates = false): array
    {
        // products?
        if (0 === $this->count()) {
            return [];
        }

        /** @var Product[] $products */
        $products = static::randomElements($this->getEntities(), $count, $allowDuplicates);
        if (\count($products) < 2) {
            return $products;
        }

        \usort($products, static function (Product $a, Product $b): int {
            $result = \strnatcasecmp($a->getCategoryCode(), $b->getCategoryCode());
            if (0 !== $result) {
                return $result;
            }

            return $a->compare($b);
        });

        return $products;
    }

    /**
     * Gets a random product's supplier.
     */
    public function productSupplier(): ?string
    {
        /** @phpstan-var string|null */
        return $this->distinctValue('supplier', true);
    }

    /**
     * Gets a random product's unit.
     */
    public function productUnit(): ?string
    {
        /** @phpstan-var string|null */
        return $this->distinctValue('unit', true);
    }
}
