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

use App\Form\FormHelper;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * User login type.
 *
 * @author Laurent Muller
 */
class UserLoginType extends AbstractUserCaptchaType
{
    /**
     * Constructor.
     */
    public function __construct(CaptchaImageService $service, ApplicationService $application)
    {
        parent::__construct($service, $application);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('username')
            ->autocomplete('username')
            ->maxLength(180)
            ->add(UserNameType::class);

        $helper->field('password')
            ->autocomplete('current-password')
            ->maxLength(255)
            ->addPassordType();

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
