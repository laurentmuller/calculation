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
 */
class CalculationGroupType extends AbstractEntityType
{
    /**
     * @var GroupTransformer
     */
    private $transformer;

    /**
     * Constructor.
     *
     * @param GroupTransformer $transformer the transformer to convert the group field
     */
    public function __construct(GroupTransformer $transformer)
    {
        parent::__construct(CalculationGroup::class);
        $this->transformer = $transformer;
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
        // default
        $helper->field('group')->addHiddenType();
        $helper->field('code')->addHiddenType();

        // items
        $helper->field('categories')
            ->updateOption('prototype_name', '__groupIndex__')
            ->addCollectionType(CalculationCategoryType::class);
    }
}
