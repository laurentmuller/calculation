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

namespace App\Form\User;

use App\Form\AbstractChoiceType;
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
            'rights.export' => EntityVoter::getAttributeMask(EntityVoterInterface::ATTRIBUTE_EXPORT),
        ];
    }
}
