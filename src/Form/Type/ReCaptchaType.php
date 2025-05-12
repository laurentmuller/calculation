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

namespace App\Form\Type;

use App\Service\RecaptchaService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The Google reCaptcha type.
 *
 * @extends AbstractType<HiddenType>
 */
class ReCaptchaType extends AbstractType implements EventSubscriberInterface
{
    private const RECAPTCHA_URL = 'https://www.google.com/recaptcha/api.js?render=';

    public function __construct(
        private readonly RecaptchaService $service,
        private readonly RequestStack $requestStack
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber($this);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $key = $this->service->getSiteKey();

        $view->vars += [
            'recaptcha_url' => self::RECAPTCHA_URL . $key,
        ];

        $view->vars['attr'] += [
            'class' => 'recaptcha',
            'data-key' => $key,
            'data-event' => $options['event'],
            'data-selector' => $options['selector'],
            'data-action' => $options['expectedAction'],
        ];
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('mapped', false);

        $resolver->define('expectedAction')
            ->default($this->service->getExpectedAction())
            ->allowedTypes('string')
            ->required();

        $resolver->define('selector')
            ->default('[data-toggle="recaptcha"]')
            ->allowedTypes('string')
            ->required();

        $resolver->define('event')
            ->default('click')
            ->allowedTypes('string')
            ->required();

        $resolver->define('scoreThreshold')
            ->default($this->service->getScoreThreshold())
            ->allowedTypes('float');

        $resolver->define('challengeTimeout')
            ->default($this->service->getChallengeTimeout())
            ->allowedTypes('int');
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'recaptcha';
    }

    #[\Override]
    public function getParent(): string
    {
        return HiddenType::class;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'onPostSubmit',
        ];
    }

    public function onPostSubmit(PostSubmitEvent $event): void
    {
        $form = $event->getForm();
        $data = (string) $event->getData();
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            $error = $this->service->translateError('no-request');
            $form->addError(new FormError($error));

            return;
        }

        $this->updateService($form->getConfig());
        $response = $this->service->verify($data, $request);
        if ($response->isSuccess()) {
            return;
        }

        $errors = $this->service->translateErrors($response);
        foreach ($errors as $error) {
            $form->addError(new FormError($error));
        }
    }

    /**
     * @phpstan-param FormConfigInterface<array> $config
     *
     * @psalm-param FormConfigInterface $config
     */
    private function updateService(FormConfigInterface $config): void
    {
        if ($config->hasOption('expectedAction')) {
            $expectedAction = (string) $config->getOption('expectedAction', $this->service->getExpectedAction());
            $this->service->setExpectedAction($expectedAction);
        }
        if ($config->hasOption('scoreThreshold')) {
            $scoreThreshold = (float) $config->getOption('scoreThreshold', $this->service->getScoreThreshold());
            $this->service->setScoreThreshold($scoreThreshold);
        }
        if ($config->hasOption('challengeTimeout')) {
            $challengeTimeout = (int) $config->getOption('challengeTimeout', $this->service->getChallengeTimeout());
            $this->service->setChallengeTimeout($challengeTimeout);
        }
    }
}
