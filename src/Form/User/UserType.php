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
use App\Form\AbstractEntityType;
use App\Form\FormHelper;
use App\Utils\FormatUtils;
use Symfony\Component\Form\Event\PreSetDataEvent;

/**
 * User edit type.
 *
 * @template-extends AbstractEntityType<User>
 */
class UserType extends AbstractEntityType
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('id')
            ->disabled()
            ->addHiddenType();

        $helper->field('username')
            ->addUserNameType(false);

        $helper->field('email')
            ->addEmailType();

        $helper->field('plainPassword')
            ->addRepeatPasswordType();

        $helper->field('role')
            ->add(RoleChoiceType::class);

        $helper->field('enabled')
            ->addTrueFalseType('common.value_enabled', 'common.value_disabled');

        $helper->field('lastLogin')
            ->updateOption('value_transformer', $this->formatLastLogin(...))
            ->updateOption('empty_value', 'common.value_none')
            ->widgetClass('text-center')
            ->addPlainType();

        $helper->field('imageFile')
            ->updateOption('maxsize', '10mi')
            ->addVichImageType();

        $helper->listenerPreSetData(fn (PreSetDataEvent $event) => $this->onPreSetData($event));
    }

    /**
     * Format the last login date.
     */
    private function formatLastLogin(\DateTimeInterface|string $lastLogin): ?string
    {
        if ($lastLogin instanceof \DateTimeInterface) {
            return FormatUtils::formatDateTime($lastLogin);
        }

        return null;
    }

    /**
     * Handles the preset data event.
     */
    private function onPreSetData(PreSetDataEvent $event): void
    {
        /** @var User $user */
        $user = $event->getData();
        $form = $event->getForm();
        if ($user->isNew()) {
            // the password must be set, if not; the form is not valid
            $user->setPassword('123456');
            $event->setData($user);
            $form->remove('lastLogin');
        } else {
            $form->remove('plainPassword');
        }
    }
}
