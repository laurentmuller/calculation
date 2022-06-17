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

namespace App\Form\User;

use App\Enums\EntityPermission;
use Elao\Enum\Bridge\Symfony\Form\Type\FlagBagType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to edit entity permissions.
 */
class AttributeRightType extends AbstractType
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
            'class' => EntityPermission::class,
            'choices' => EntityPermission::sorted(),
        ]);
    }

    public function getParent(): ?string
    {
        return FlagBagType::class;
    }
}
