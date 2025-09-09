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

use App\Entity\CalculationGroup;
use App\Form\AbstractEntityType;
use App\Form\DataTransformer\EntityTransformer;
use App\Form\FormHelper;
use App\Repository\GroupRepository;

/**
 * Calculation group edit type.
 *
 * @template-extends AbstractEntityType<CalculationGroup>
 */
class CalculationGroupType extends AbstractEntityType
{
    public function __construct(private readonly GroupRepository $repository)
    {
        parent::__construct(CalculationGroup::class);
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('group')
            ->modelTransformer(new EntityTransformer($this->repository))
            ->addHiddenType();
        $helper->field('code')
            ->addHiddenType();
        $helper->field('position')
            ->addHiddenType();
        $helper->field('categories')
            ->updateOption('prototype_name', '__categoryIndex__')
            ->addCollectionType(CalculationCategoryType::class);
    }
}
