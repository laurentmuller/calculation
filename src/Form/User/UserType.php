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
            ->addPlainType(true);

        $helper->field('imageFile')
            ->updateOption('maxsize', '10mi')
            ->addVichImageType();

        // add listener
        $helper->addPreSetDataListener([$this, 'onPreSetData']);
    }
}
