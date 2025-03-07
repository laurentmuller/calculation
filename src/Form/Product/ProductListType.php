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

namespace App\Form\Product;

use App\Entity\Product;
use App\Form\AbstractListEntityType;
use App\Repository\ProductRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to display a list of products grouped by groups and categories.
 *
 * @template-extends AbstractListEntityType<Product>
 */
class ProductListType extends AbstractListEntityType
{
    public function __construct()
    {
        parent::__construct(Product::class);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'choice_label' => 'description',
            'group_by' => $this->getGroupBy(...),
            'query_builder' => fn (ProductRepository $repository): QueryBuilder => $repository->getQueryBuilderByCategory(),
        ]);
    }

    private function getGroupBy(Product $product): string
    {
        return \sprintf('%s - %s', $product->getGroupCode(), $product->getCategoryCode());
    }
}
