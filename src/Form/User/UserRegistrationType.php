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
use App\Traits\TranslatorAwareTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Type to register a new user.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class UserRegistrationType extends AbstractUserCaptchaType implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('username')
            ->label('user.fields.username')
            ->autocomplete('username')
            ->maxLength(User::MAX_USERNAME_LENGTH)
            ->add(UserNameType::class);

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
            ->addCheckboxType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): ?string
    {
        return 'user.fields.';
    }
}
