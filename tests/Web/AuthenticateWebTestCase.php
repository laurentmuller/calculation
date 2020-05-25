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

namespace App\Tests\Web;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Abstract class for authenticate user.
 *
 * @author Laurent Muller
 */
abstract class AuthenticateWebTestCase extends WebTestCase
{
    public const ROLE_ADMIN = User::ROLE_ADMIN;

    public const ROLE_DISABLED = 'ROLE_DISABLED';

    public const ROLE_FAKE = 'ROLE_FAKE';

    public const ROLE_SUPER_ADMIN = User::ROLE_SUPER_ADMIN;
    public const ROLE_USER = User::ROLE_DEFAULT;

    /**
     * @var KernelBrowser
     */
    protected $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function doEcho(string $name, $value): void
    {
        // echo \sprintf("\n%-15s: %s", $name, $value);
    }

    protected function loadUser(string $username, bool $verify = true): ?User
    {
        /** @var UserRepository $repository */
        $repository = self::$container->get(UserRepository::class);
        $this->assertNotNull($repository, 'The user respository is null.');

        /** @var User $user */
        $user = $repository->findOneBy([
            'username' => \strtolower($username),
        ]);

        if ($verify) {
            $this->assertNotNull($user, "The user '$username' is null.");
            $this->doEcho('UserName', $user->getUsername());
            $this->doEcho('Role', \json_encode($user->getRoles()));
        }

        return $user;
    }

    protected function loginUser(User $user, string $firewall = 'main'): void
    {
        $token = new UsernamePasswordToken($user, null, $firewall, $user->getRoles());

        $session = self::$container->get('session');
        $session->set('_security_' . $firewall, \serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
}
