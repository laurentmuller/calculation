<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Listener;

use App\Controller\IndexController;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseNullableUserEvent;
use FOS\UserBundle\FOSUserEvents;
use ReCaptcha\ReCaptcha;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * User security events listener.
 *
 * @internal
 */
final class UserEventListener extends AbstractListener implements EventSubscriberInterface
{
    /**
     * The application.
     *
     * @var ApplicationService
     */
    private $application;
    /**
     * The URL generator.
     *
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * The captcha service.
     *
     * @var CaptchaImageService
     */
    private $service;

    /**
     * The user name parameter.
     *
     * @var string
     */
    private $switchParameterName;

    /**
     * Constructor.
     *
     * @param ContainerInterface    $container           the container
     * @param SessionInterface      $session             the session
     * @param TranslatorInterface   $translator          the translator
     * @param UrlGeneratorInterface $router              the router
     * @param CaptchaImageService   $service             the captcha image service
     * @param ApplicationService    $application         the application service
     * @param string                $switchParameterName the switch user parameter name in query
     */
    public function __construct(ContainerInterface $container, SessionInterface $session, TranslatorInterface $translator, UrlGeneratorInterface $router, CaptchaImageService $service, ApplicationService $application, string $switchParameterName = '_switch_user')
    {
        parent::__construct($container, $session, $translator);

        $this->router = $router;
        $this->service = $service;
        $this->application = $application;
        $this->switchParameterName = $switchParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FOSUserEvents::CHANGE_PASSWORD_SUCCESS => 'onSuccess',
            FOSUserEvents::PROFILE_EDIT_SUCCESS => 'onSuccess',
            FOSUserEvents::RESETTING_RESET_SUCCESS => ['onResetSuccess', 10],
            // FOSUserEvents::SECURITY_IMPLICIT_LOGIN => 'onImplicitLogin',
            FOSUserEvents::RESETTING_SEND_EMAIL_INITIALIZE => 'onSendEmailInitialize',
            //FOSUserEvents::RESETTING_RESET_INITIALIZE => ['onResetInitialize', 10],
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
            SecurityEvents::SWITCH_USER => 'onSwitchUser',
        ];
    }

//     /**
//      * Handles the implicit login event.
//      *
//      * @param UserEvent $event
//      */
//     public function onImplicitLogin(UserEvent $event): void
//     {
//         $user = $event->getUser();
//         $message = 'security.login.success';
//         $params = [
//             '%username%' => $user->getUsername(),
//             '%appname%' => $this->appName,
//         ];
//         $this->succesTrans($message, $params, 'FOSUserBundle');
//         //$this->setLogin($event->getUser());
//     }

    /**
     * Handle the intercative login event.
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        // clear
        //CaptchaImageService::clearSession($this->session);

        // check
        $request = $event->getRequest();
        $message = $this->validateCaptcha($request);
        if ($message) {
            $error = new CustomUserMessageAuthenticationException($message);
            $error->setToken($event->getAuthenticationToken());
            if ($this->session) {
                $this->session->set(Security::AUTHENTICATION_ERROR, $error);
            }
            throw $error;
        }
        $this->service->clear();
    }

    /**
     * Handle the reset initialize event.
     */
//     public function onResetInitialize(GetResponseUserEvent $event): void
//     {
//         $user = $event->getUser();
//         $request = $event->getRequest();

//         $factory = $this->container->get('form.factory');
//         $form = $factory->create(FosUserResettingFormType::class, $user);
//         $form->handleRequest($request);
//         if ($form->isSubmitted() && $form->isValid()) {
//             if (!$message = $this->validateCaptcha($request)) {
//                 return;
//             }

//             $error = new CustomUserMessageAuthenticationException($message);
//             if ($this->session) {
//                 $this->session->set(Security::AUTHENTICATION_ERROR, $error);
//             }

//             $parameters = [
//                 'token' => $user->getConfirmationToken(),
//                 'form' => $form->createView(),
//                 'error' => $error,
//             ];

//             // render
//             /** @var \Symfony\Component\Templating\EngineInterface $engine */
//             $engine = $this->container->get('templating');
//             $content = $engine->render('@FOSUser/Resetting/reset.html.twig', $parameters);
//             $response = new Response($content);
    // //             $event->setResponse($response);
    // //             $event->stopPropagation();
//         }
//     }

    /**
     * Handle the reset success event.
     */
    public function onResetSuccess(FormEvent $event): void
    {
//         $request = $event->getRequest();
//         if ($message = $this->validateCaptcha($request)) {
//             $error = new CustomUserMessageAuthenticationException($message);
//             if ($this->session) {
//                 $this->session->set(Security::AUTHENTICATION_ERROR, $error);
//             }

//             $form = $event->getForm();
//             /** @var User $user */
//             $user = $form->getData();
//             $token = $user->getConfirmationToken();

//             $parameters = [
//                 'form' => $form->createView(),
//                 'token' => $token,
//                 'error' => $error,
//             ];

//             // render
//             /** @var \Symfony\Component\Templating\EngineInterface $engine */
//             $engine = $this->container->get('templating');
//             $content = $engine->render('@FOSUser/Resetting/reset.html.twig', $parameters);
//             $response = new Response($content);
//             $event->setResponse($response);
//             $event->stopPropagation();

//             return;
//         }

        // home page
        $this->setHomePageResponse($event);
    }

    /**
     * Handles the send email initialize event.
     */
    public function onSendEmailInitialize(GetResponseNullableUserEvent $event): void
    {
        $request = $event->getRequest();
        //$message = $this->validateRecaptcha($request);
        $message = $this->validateCaptcha($request);
        if ($message) {
            // save exception
            $e = new CustomUserMessageAuthenticationException($message);
            if ($this->session) {
                $this->session->set(Security::AUTHENTICATION_ERROR, $e);
            }

            // get user name
            $parameters = ['error' => $e];
            if ($user = $event->getUser()) {
                $parameters['username'] = $user->getUsername();
            }

            // redirect
            $response = new RedirectResponse($this->generateUrl('fos_user_resetting_request', $parameters));
            $event->setResponse($response);
        }
    }

    /**
     * Handle the change succes event.
     */
    public function onSuccess(FormEvent $event, string $eventName): void
    {
        $this->setHomePageResponse($event);
    }

    /**
     * Handles the switch user event.
     */
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        // get user name parameter
        $request = $event->getRequest();
        $key = $this->switchParameterName;
        $username = $request->get($key, $request->headers->get($key));
        if (!$username) {
            return;
        }

        // exit?
        if (SwitchUserListener::EXIT_VALUE === $username) {
            $this->succesTrans('user.switch.exit.sucess');
        } else {
            $this->succesTrans('user.switch.take.sucess', ['%name%' => $username]);
        }
    }

    /**
     * Generates a URL or path for a specific route.
     *
     * @param string $route         the name of the route
     * @param mixed  $parameters    an array of parameters
     * @param int    $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string the generated URL
     */
    private function generateURL(string $route, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->router->generate($route, $parameters, $referenceType);
    }

    /**
     * Sets home page as the response of the given event.
     *
     * @param formEvent $event the event to update
     */
    private function setHomePageResponse(FormEvent $event): void
    {
        $url = $this->generateURL(IndexController::HOME_PAGE);
        $response = RedirectResponse::create($url);
        $event->setResponse($response);
    }

    /**
     * Validate the captcha token.
     *
     * @param Request $request the request
     *
     * @return string|null the error message if fail, null if valid
     */
    private function validateCaptcha(Request $request): ?string
    {
        // captcha used?
        if (!$this->application->isDisplayCaptcha()) {
            return null;
        }
        if (!$this->service->validateTimeout()) {
            return 'captcha.timeout';
        }
        if (!$this->service->validateToken($request->get('_captcha'))) {
            return 'captcha.invalid';
        }

        return null;
    }

    /**
     * Validate the reCaptcha token.
     *
     * @param Request $request the request
     *
     * @return string|null the error message if fail, null if valid
     */
    private function validateRecaptcha(Request $request): ?string
    {
        // captcha used?
        if (!$this->application->isDisplayCaptcha()) {
            return null;
        }

        // if (!$this->container->getParameter('recaptcha_used')) {
        // }

        // get values
        $site_action = 'login';
        $token = $request->request->get('_recaptcha');
        $hostname = $request->server->get('HTTP_HOST');
        $secret = $this->container->getParameter('recaptcha_secret');

        // initialize
        $recaptcha = new ReCaptcha($secret);
        $recaptcha->setExpectedAction($site_action)
            ->setExpectedHostname($hostname)
            ->setChallengeTimeout(60)
            ->setScoreThreshold(0.5);

        // verify
        $result = $recaptcha->verify($token);
        if ($result->isSuccess()) {
            return null;
        }

        // build error
        $errors = $result->getErrorCodes();
        $error = empty($errors) ? 'unknown-error' : $errors[0];

        return "recaptcha.{$error}";
    }
}
