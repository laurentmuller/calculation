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
use App\Form\AbstractCategoryItemType;
use App\Form\FormHelper;
use App\Interfaces\EntityInterface;

/**
 * Product edit type.
 *
 * @extends AbstractCategoryItemType<Product>
 */
class ProductType extends AbstractCategoryItemType
{
    public function __construct()
    {
        parent::__construct(Product::class);
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        parent::addFormFields($helper);
        $helper->field('description')
            ->maxLength(EntityInterface::MAX_STRING_LENGTH)
            ->addTextType();
        $helper->field('price')
            ->addMoneyType();
    }
}
