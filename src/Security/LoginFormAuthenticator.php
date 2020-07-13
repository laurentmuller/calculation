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

namespace App\Security;

use App\Controller\IndexController;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Login form authenticator.
 *
 * @author Laurent Muller
 */
class LoginFormAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    /**
     * The login route name.
     */
    public const LOGIN_ROUTE = 'app_login';

    private $encoder;
    private $generator;
    private $repository;
    private $tokenManager;

    public function __construct(UserRepository $repository, UrlGeneratorInterface $generator, CsrfTokenManagerInterface $tokenManager, UserPasswordEncoderInterface $encoder)
    {
        $this->repository = $repository;
        $this->generator = $generator;
        $this->tokenManager = $tokenManager;
        $this->encoder = $encoder;
    }

    /**
     * {@inheritdoc}
     *
     * @see AuthenticatorInterface
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $this->encoder->isPasswordValid($user, $credentials['password']);
    }

    /**
     * {@inheritdoc}
     *
     * @see AuthenticatorInterface
     */
    public function getCredentials(Request $request): array
    {
        $credentials = [
            'username' => $request->request->get('username'),
            'password' => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];

        $request->getSession()->set(Security::LAST_USERNAME, $credentials['username']);

        return $credentials;
    }

    /**
     * {@inheritdoc}
     *
     * @see PasswordAuthenticatedInterface
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }

    /**
     * {@inheritdoc}
     *
     * @see AuthenticatorInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->tokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $user = $this->repository->findOneBy(['username' => $credentials['username']]);

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Username could not be found.');
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     *
     * @see AuthenticatorInterface
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): Response
    {
        $session = $request->getSession();
        if ($targetPath = $this->getTargetPath($session, $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->getHomeUrl());
    }

    /**
     * {@inheritdoc}
     *
     * @see AuthenticatorInterface
     */
    public function supports(Request $request): bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
            && $request->isMethod(Request::METHOD_POST);
    }

    /**
     * Return the URL to the index (home) page.
     */
    protected function getHomeUrl(): string
    {
        return $this->generator->generate(IndexController::INDEX_ROUTE);
    }

    /**
     * {@inheritdoc}
     *
     * @see AbstractFormLoginAuthenticator
     */
    protected function getLoginUrl(): string
    {
        return $this->generator->generate(self::LOGIN_ROUTE);
    }
}
