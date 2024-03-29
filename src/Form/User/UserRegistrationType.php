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
use App\Traits\TranslatorAwareTrait;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Type to register a new user.
 */
class UserRegistrationType extends AbstractUserCaptchaType implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', User::class);
    }

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

        parent::addFormFields($helper);

        $helper->field('agreeTerms')
            ->notMapped()
            ->rowClass('mb-0')
            ->label('registration.agreeTerms.label')
            ->updateAttribute('data-error', $this->trans('registration.agreeTerms.error'))
            ->addCheckboxType(false);

        $helper->listenerPreSetData($this->onPreSetData(...));
    }

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
        $user->setPassword('123456');
    }
}
