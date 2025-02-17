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

use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Type to clear all requested passwords.
 *
 * @extends AbstractType<ChoiceType>
 */
class ResetAllPasswordType extends AbstractType
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->onPreSubmit(...));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'expanded' => true,
            'multiple' => true,
            'label' => 'user.list.title',
            'choice_value' => 'id',
            'choice_label' => 'NameAndEmail',
            'choice_translation_domain' => false,
            'choices' => $this->repository->getResettableUsers(),
            'row_attr' => ['class' => 'ms-5 mb-3'],
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    private function onPreSubmit(PreSubmitEvent $event): void
    {
        /** @var array $data */
        $data = $event->getData();
        $form = $event->getForm();
        if ($form->isRequired() && [] === \array_filter($data)) {
            $form->addError(new FormError($this->translator->trans('user.reset_all.error')));
        }
    }
}
