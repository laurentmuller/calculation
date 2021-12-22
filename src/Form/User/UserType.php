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

use App\Entity\User;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;
use App\Form\Type\EnabledDisabledType;
use App\Form\Type\PlainType;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * User edit type.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityType<User>
 */
class UserType extends AbstractEntityType
{
    private UserPasswordHasherInterface $hasher;

    /**
     * Constructor.
     */
    public function __construct(UserPasswordHasherInterface $hasher)
    {
        parent::__construct(User::class);
        $this->hasher = $hasher;
    }

    /**
     * Handles the preset data event.
     */
    public function onPreSetData(FormEvent $event): void
    {
        /** @var User $user */
        $user = $event->getData();
        $form = $event->getForm();
        if ($user->isNew()) {
            $form->remove('lastLogin');
        } else {
            $form->remove('plainPassword');
        }
    }

    /**
     * Handles the submit event.
     */
    public function onSubmit(SubmitEvent $event): void
    {
        $form = $event->getForm();
        if ($form->has('plainPassword')) {
            /** @var User $user */
            $user = $event->getData();
            $plainPassword = $form->get('plainPassword')->getData();
            $encodedPassword = $this->hasher->hashPassword($user, $plainPassword);
            $user->setPassword($encodedPassword);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('id')
            ->addHiddenType();

        $helper->field('username')
            ->minLength(2)
            ->maxLength(180)
            ->add(UserNameType::class);

        $helper->field('email')
            ->maxLength(180)
            ->addEmailType();

        $helper->field('plainPassword')
            ->addRepeatPasswordType();

        $helper->field('role')
            ->add(RoleChoiceType::class);

        $helper->field('enabled')
            ->add(EnabledDisabledType::class);

        $helper->field('lastLogin')
            ->widgetClass('text-center')
            ->updateOptions([
                'date_format' => PlainType::FORMAT_SHORT,
                'time_format' => PlainType::FORMAT_SHORT,
                'empty_value' => 'common.value_none', ])
            ->addPlainType(true);

        $helper->field('imageFile')
            ->updateOption('maxsize', '10mi')
            ->addVichImageType();

        // add listeners
        $helper->addPreSetDataListener(function (FormEvent $event): void {
            $this->onPreSetData($event);
        });
        $helper->addSubmitListener(function (SubmitEvent $event): void {
            $this->onSubmit($event);
        });
    }
}
