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

namespace App\Form\Extension;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * Extends VichImageType to use within the FileInput plugin.
 *
 * @extends AbstractFileTypeExtension<VichImageType>
 */
class VichImageTypeExtension extends AbstractFileTypeExtension
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('placeholder', null);
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [VichImageType::class];
    }
}
