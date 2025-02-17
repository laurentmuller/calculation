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

namespace App\Form\Parameters;

use App\Entity\CalculationState;
use App\Entity\Category;
use App\Form\CalculationState\CalculationStateListType;
use App\Form\Category\CategoryListType;
use App\Form\DataTransformer\IdentifierTransformer;
use App\Form\FormHelper;
use App\Parameter\DefaultParameter;
use Doctrine\ORM\EntityManagerInterface;

class DefaultParameterType extends AbstractParameterType
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('stateId')
            ->modelTransformer($this->getCalculationStateTransformer())
            ->label('parameters.fields.default_state')
            ->add(CalculationStateListType::class);

        $helper->field('categoryId')
            ->modelTransformer($this->getCategoryTransformer())
            ->label('parameters.fields.default_category')
            ->add(CategoryListType::class);

        $helper->field('minMargin')
            ->label('parameters.fields.minimum_margin')
            ->percent(true)
            ->addPercentType(0);
    }

    #[\Override]
    protected function getParameterClass(): string
    {
        return DefaultParameter::class;
    }

    /**
     * @psalm-return IdentifierTransformer<CalculationState>
     */
    private function getCalculationStateTransformer(): IdentifierTransformer
    {
        return new IdentifierTransformer($this->manager->getRepository(CalculationState::class));
    }

    /**
     * @psalm-return IdentifierTransformer<Category>
     */
    private function getCategoryTransformer(): IdentifierTransformer
    {
        return new IdentifierTransformer($this->manager->getRepository(Category::class));
    }
}
