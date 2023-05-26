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

namespace App\Form\Calculation;

use App\Entity\CalculationCategory;
use App\Form\AbstractEntityType;
use App\Form\DataTransformer\CategoryTransformer;
use App\Form\FormHelper;

/**
 * Calculation category edit type.
 *
 * @template-extends AbstractEntityType<CalculationCategory>
 */
class CalculationCategoryType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct(private readonly CategoryTransformer $transformer)
    {
        parent::__construct(CalculationCategory::class);
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('category')
            ->modelTransformer($this->transformer)
            ->addHiddenType()
            ->field('code')->addHiddenType()
            ->field('position')->addHiddenType();

        $helper->field('items')
            ->updateOption('prototype_name', '__itemIndex__')
            ->addCollectionType(CalculationItemType::class);
    }
}
