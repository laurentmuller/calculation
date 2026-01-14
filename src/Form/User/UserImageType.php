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

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * @extends AbstractType<User>
 */
class UserImageType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'maxsize' => '10mi',
            'required' => false,
            'download_uri' => false,
            'translation_domain' => 'messages',
            'delete_label' => 'user.edit.delete_image',
            'attr' => [
                'accept' => 'image/gif,image/jpeg,image/png,image/bmp',
            ],
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return VichImageType::class;
    }
}
