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
use App\Form\Type\EnabledDisabledType;
use App\Util\FormatUtils;
use Symfony\Component\Form\FormEvent;

/**
 * User edit type.
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
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('id')
            ->addHiddenType();

        $helper->field('username')
            ->minLength(2)
            ->maxLength(User::MAX_USERNAME_LENGTH)
            ->add(UserNameType::class);

        $helper->field('email')
            ->addEmailType();

        $helper->field('plainPassword')
            ->addRepeatPasswordType();

        $helper->field('role')
            ->add(RoleChoiceType::class);

        $helper->field('enabled')
            ->add(EnabledDisabledType::class);

        $helper->field('lastLogin')
            ->updateOption('value_transformer', $this->formatLastLogin(...))
            ->updateOption('empty_value', 'common.value_none')
            ->widgetClass('text-center')
            ->addPlainType(true);

        $helper->field('imageFile')
            ->updateOption('maxsize', '10mi')
            ->addVichImageType();

        $helper->addPreSetDataListener($this->onPreSetData(...));
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
    private function onPreSetData(FormEvent $event): void
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
}
