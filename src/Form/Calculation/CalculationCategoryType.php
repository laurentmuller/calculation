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

use App\Entity\CalculationCategory;
use App\Form\AbstractEntityType;
use App\Form\DataTransformer\CategoryTransformer;
use App\Form\FormHelper;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Calculation category edit type.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityType<CalculationCategory>
 */
class CalculationCategoryType extends AbstractEntityType
{
    private CategoryTransformer $transformer;

    /**
     * Constructor.
     */
    public function __construct(CategoryTransformer $transformer)
    {
        parent::__construct(CalculationCategory::class);
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        // add transformer
        $builder->get('category')
            ->addModelTransformer($this->transformer);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('category')->addHiddenType()
            ->field('code')->addHiddenType()
            ->field('position')->addHiddenType();

        $helper->field('items')
            ->updateOption('prototype_name', '__itemIndex__')
            ->addCollectionType(CalculationItemType::class);
    }
}
