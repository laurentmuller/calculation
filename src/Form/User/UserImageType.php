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
use App\Form\Type\BoolHiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * @extends AbstractType<User>
 */
class UserImageType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $allowDelete = $options['allow_delete'];
        if (\is_bool($allowDelete) && $allowDelete) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, $this->onPreSetData(...));
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'maxsize' => '10mi',
            'required' => false,
            'download_uri' => false,
            'translation_domain' => 'messages',
            'delete_label' => 'user.edit.delete_image',
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return VichImageType::class;
    }

    private function onPreSetData(PreSetDataEvent $event): void
    {
        $form = $event->getForm();
        if ($form->has('delete')) {
            $form->add('delete', BoolHiddenType::class);
        }
    }
}
