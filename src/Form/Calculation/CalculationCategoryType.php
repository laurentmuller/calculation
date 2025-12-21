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
use App\Form\DataTransformer\EntityTransformer;
use App\Form\FormHelper;
use App\Repository\CategoryRepository;

/**
 * Calculation category edit type.
 *
 * @extends AbstractEntityType<CalculationCategory>
 */
class CalculationCategoryType extends AbstractEntityType
{
    public function __construct(private readonly CategoryRepository $repository)
    {
        parent::__construct(CalculationCategory::class);
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('category')
            ->modelTransformer(new EntityTransformer($this->repository))
            ->addHiddenType();
        $helper->field('code')
            ->addHiddenType();
        $helper->field('position')
            ->addHiddenType();
        $helper->field('items')
            ->addCollectionType(CalculationItemType::class, '__itemIndex__');
    }
}
