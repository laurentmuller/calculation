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
use App\Form\FormHelper;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to register a new user.
 *
 * @author Laurent Muller
 */
class UserRegistrationType extends UserCaptchaType
{
    /**
     * Constructor.
     */
    public function __construct(CaptchaImageService $service, ApplicationService $application)
    {
        parent::__construct($service, $application);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('email')
            ->label('user.fields.email')
            ->addEmailType();

        $helper->field('username')
            ->label('user.fields.username')
            ->autocomplete('username')
            ->maxLength(180)
            ->add(UserNameType::class);

        $helper->field('plainPassword')
            ->addRepeatPasswordType();

        parent::addFormFields($helper);
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): ?string
    {
        return 'user.fields.';
    }
}
