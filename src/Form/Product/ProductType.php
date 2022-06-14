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

use App\Entity\AbstractEntity;
use App\Entity\Product;
use App\Form\AbstractEntityType;
use App\Form\Category\CategoryListType;
use App\Form\FormHelper;

/**
 * Product edit type.
 *
 * @template-extends AbstractEntityType<Product>
 */
class ProductType extends AbstractEntityType
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
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('description')
            ->maxLength(AbstractEntity::MAX_STRING_LENGTH)
            ->addTextType();

        $helper->field('unit')
            ->autocomplete('off')
            ->maxLength(15)
            ->notRequired()
            ->addTextType();

        $helper->field('price')
            ->addNumberType();

        $helper->field('category')
            ->add(CategoryListType::class);

        $helper->field('supplier')
            ->autocomplete('off')
            ->maxLength(AbstractEntity::MAX_STRING_LENGTH)
            ->notRequired()
            ->addTextType();
    }
}
