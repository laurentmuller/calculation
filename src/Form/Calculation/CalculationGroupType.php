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
    /**
     * @var EntityTransformer<\App\Entity\Group>
     */
    private readonly EntityTransformer $transformer;

    /**
     * Constructor.
     */
    public function __construct(GroupRepository $repository)
    {
        parent::__construct(CalculationGroup::class);
        $this->transformer = new EntityTransformer($repository);
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('group')
            ->modelTransformer($this->transformer)
            ->addHiddenType()
            ->field('code')->addHiddenType()
            ->field('position')->addHiddenType();

        $helper->field('categories')
            ->updateOption('prototype_name', '__categoryIndex__')
            ->addCollectionType(CalculationCategoryType::class);
    }
}
