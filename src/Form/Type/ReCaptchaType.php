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
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<HiddenType>
 */
class ReCaptchaType extends AbstractType implements EventSubscriberInterface
{
    public function __construct(
        private readonly RecaptchaService $service,
        private readonly RequestStack $requestStack
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber($this);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attr'] += [
            'data-toggle' => 'recaptcha',
            'data-site-key' => $this->service->getSiteKey(),
            'data-expected-action' => $this->service->getExpectedAction(),
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('expectedAction')
            ->default($this->service->getExpectedAction())
            ->allowedTypes('string');

        $resolver->define('scoreThreshold')
            ->default($this->service->getScoreThreshold())
            ->allowedTypes('float');

        $resolver->define('challengeTimeout')
            ->default($this->service->getChallengeTimeout())
            ->allowedTypes('int');
    }

    public function getParent(): ?string
    {
        return HiddenType::class;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'onPostSubmit',
        ];
    }

    public function onPostSubmit(PostSubmitEvent $event): void
    {
        $form = $event->getForm();
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            $error = $this->service->translateError('recaptcha.no-request');
            $form->addError(new FormError($error));

            return;
        }

        $data = (string) $event->getData();
        $response = $this->service->verify($data, $request);
        if ($response->isSuccess()) {
            return;
        }

        $errors = $this->service->translateErrors($response);
        foreach ($errors as $error) {
            $form->addError(new FormError($error));
        }
    }
}
