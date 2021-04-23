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
use Symfony\Component\Form\FormEvent;

/**
 * User edit type.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityType<User>
 */
class UserType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(User::class);
    }

    /**
     * Handles the preset data event.
     */
    public function onPreSetData(FormEvent $event): void
    {
        /* @var User $user */
        $user = $event->getData();
        $form = $event->getForm();
        if ($user->isNew()) {
            $form->remove('lastLogin');
            $form->remove('imageFile');
        } else {
            $form->remove('plainPassword');
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
            ->className('text-center')
            ->updateOption('date_format', PlainType::FORMAT_SHORT)
            ->updateOption('time_format', PlainType::FORMAT_SHORT)
            ->updateOption('empty_value', 'common.value_none')
            ->addPlainType(true);

        $helper->field('imageFile')
            ->updateOption('maxsize', '10mi')
            ->addVichImageType();

        // add listener
        $helper->addPreSetDataListener([$this, 'onPreSetData']);
    }
}
