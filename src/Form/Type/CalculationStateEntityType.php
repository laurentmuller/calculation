<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\CalculationState;
use App\Repository\CalculationStateRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to display a list of calculation state entities.
 *
 * @author Laurent Muller
 */
class CalculationStateEntityType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => CalculationState::class,
            'placeholder' => false,
            'choice_label' => 'code',
            'query_builder' => function (CalculationStateRepository $r) {
                return $r->getSortedBuilder();
            },
            // 'choice_attr' => function (CalculationState $choice, $key, $value) {
            // $text = $choice->getCode();
            // $color = $choice->getColor();
            // return [
            // 'data-content' => "<span class='drowpdown-state-color' style='background-color:$color;'></span class='drowpdown-state-text'><span>$text</span>",
            // ];
            // },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }
}
