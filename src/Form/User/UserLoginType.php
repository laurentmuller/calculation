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

use App\Entity\AbstractEntity;
use App\Entity\User;
use App\Form\FormHelper;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;

/**
 * User login type.
 */
class UserLoginType extends AbstractUserCaptchaType
{
    /**
     * Constructor.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __construct(CaptchaImageService $service, ApplicationService $application)
    {
        parent::__construct($service, $application);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('username')
            ->autocomplete('username')
            ->maxLength(User::MAX_USERNAME_LENGTH)
            ->add(UserNameType::class);

        $helper->field('password')
            ->autocomplete('current-password')
            ->maxLength(AbstractEntity::MAX_STRING_LENGTH)
            ->addPasswordType();

        parent::addFormFields($helper);

        $helper->field('remember_me')
            ->rowClass('text-right')
            ->notRequired()
            ->addCheckboxType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): ?string
    {
        return 'security.login.';
    }
}
