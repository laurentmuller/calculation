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

use App\Interfaces\EntityVoterInterface;
use App\Security\EntityVoter;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to edit attribute rights (Edit, Add, Delete, etc...).
 *
 * @author Laurent Muller
 *
 * @see EntityVoter
 */
class AttributeRightType extends AbstractChoiceType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'expanded' => true,
            'multiple' => true,
            'required' => false,
            'choice_label' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getChoices(): array
    {
        return [
            'rights.list' => EntityVoter::getAttributeMask(EntityVoterInterface::ATTRIBUTE_LIST),
            'rights.show' => EntityVoter::getAttributeMask(EntityVoterInterface::ATTRIBUTE_SHOW),
            'rights.add' => EntityVoter::getAttributeMask(EntityVoterInterface::ATTRIBUTE_ADD),
            'rights.edit' => EntityVoter::getAttributeMask(EntityVoterInterface::ATTRIBUTE_EDIT),
            'rights.delete' => EntityVoter::getAttributeMask(EntityVoterInterface::ATTRIBUTE_DELETE),
            'rights.pdf' => EntityVoter::getAttributeMask(EntityVoterInterface::ATTRIBUTE_PDF),
        ];
    }
}
