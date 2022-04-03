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

namespace App\Form\Product;

use App\Entity\Product;
use App\Form\AbstractListEntityType;
use App\Repository\ProductRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to display a list of products grouped by categories.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractListEntityType<Product>
 */
class ProductListType extends AbstractListEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Product::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'choice_label' => 'description',
            'group_by' => fn (Product $product): string => $this->getGroupBy($product),
            'query_builder' => fn (ProductRepository $repository): QueryBuilder => $repository->getQueryBuilderByCategory(),
        ]);
    }

    private function getGroupBy(Product $product): string
    {
        $category = $product->getCategoryCode() ?? '';
        $group = $product->getGroupCode() ?? '';

        return "$category - $group";
    }
}
