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

namespace App\Form\GlobalMargin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Type to edit all global margins.
 */
class GlobalMarginsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('margins', CollectionType::class, [
            'entry_type' => GlobalMarginType::class,
            'entry_options' => ['label' => false],
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'label' => false,
            'constraints' => [new Assert\Valid()],
        ]);
    }
}
