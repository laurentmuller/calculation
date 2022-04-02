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

namespace App\Form\Calculation;

use App\Entity\CalculationGroup;
use App\Form\AbstractEntityType;
use App\Form\DataTransformer\GroupTransformer;
use App\Form\FormHelper;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Calculation group edit type.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityType<CalculationGroup>
 */
class CalculationGroupType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct(private GroupTransformer $transformer)
    {
        parent::__construct(CalculationGroup::class);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        // add transformer
        $builder->get('group')
            ->addModelTransformer($this->transformer);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('group')->addHiddenType()
            ->field('code')->addHiddenType()
            ->field('position')->addHiddenType();

        $helper->field('categories')
            ->updateOption('prototype_name', '__categoryIndex__')
            ->addCollectionType(CalculationCategoryType::class);
    }
}
