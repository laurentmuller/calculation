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
 * @template-extends AbstractEntityType<CalculationCategory>
 */
class CalculationCategoryType extends AbstractEntityType
{
    /**
     * @var EntityTransformer<\App\Entity\Category>
     */
    private readonly EntityTransformer $transformer;

    public function __construct(CategoryRepository $repository)
    {
        parent::__construct(CalculationCategory::class);
        $this->transformer = new EntityTransformer($repository);
    }

    #[\Override]
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
