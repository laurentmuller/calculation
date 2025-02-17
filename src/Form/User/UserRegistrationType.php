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
use App\Form\FormHelper;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use App\Utils\StringUtils;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Type to register a new user.
 */
class UserRegistrationType extends AbstractUserCaptchaType
{
    public function __construct(
        CaptchaImageService $service,
        ApplicationService $application,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($service, $application);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', User::class);
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('username')
            ->label('user.fields.username')
            ->addUserNameType();
        $helper->field('email')
            ->label('user.fields.email')
            ->addEmailType();
        $helper->field('plainPassword')
            ->addRepeatPasswordType();
        $helper->field('agreeTerms')
            ->notMapped()
            ->rowClass('mb-0')
            ->label('registration.agreeTerms.label')
            ->updateAttribute('data-error', $this->translator->trans('registration.agreeTerms.error'))
            ->addCheckboxType(false);
        parent::addFormFields($helper);

        $helper->listenerPreSetData(fn (PreSetDataEvent $event) => $this->onPreSetData($event));
    }

    #[\Override]
    protected function getLabelPrefix(): ?string
    {
        return 'user.fields.';
    }

    /**
     * Handles the preset data event.
     */
    private function onPreSetData(PreSetDataEvent $event): void
    {
        /** @var User $user */
        $user = $event->getData();
        if (!StringUtils::isString($user->getPassword())) {
            // the password must be set, if not; the form is not valid
            $user->setPassword('123456');
            $event->setData($user);
        }
    }
}
