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
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Type to update the user profile.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityType<User>
 */
class ProfileEditType extends AbstractEntityType
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
    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        // user name
        $helper->field('username')
            ->autocomplete('username')
            ->add(UserNameType::class);

        // email
        $helper->field('email')
            ->autocomplete('email')
            ->addEmailType();

        // current password
        $helper->field('current_password')
            ->label('user.password.current')
            ->updateOption('constraints', [
                new NotBlank(),
                new UserPassword(['message' => 'current_password.invalid']),
            ])
            ->notMapped()
            ->updateAttribute('autocomplete', 'current-password')
            ->add(PasswordType::class);

        // image
        $helper->field('imageFile')
            ->updateOption('delete_label', 'user.edit.delete_image')
            ->addVichImageType();

        // id for ajax validation
        $helper->field('id')
            ->addHiddenType();
    }
}
