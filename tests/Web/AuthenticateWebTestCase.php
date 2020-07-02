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
use App\Tests\DatabaseTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Abstract class for authenticate user.
 *
 * @author Laurent Muller
 */
abstract class AuthenticateWebTestCase extends WebTestCase
{
    use DatabaseTrait;

    public const ROLE_ADMIN = User::ROLE_ADMIN;
    public const ROLE_DISABLED = 'ROLE_DISABLED';
    public const ROLE_FAKE = 'ROLE_FAKE';
    public const ROLE_SUPER_ADMIN = User::ROLE_SUPER_ADMIN;
    public const ROLE_USER = User::ROLE_DEFAULT;

    /**
     * @var KernelBrowser
     */
    protected $client = null;

    /*
     * the debug mode
     */
    protected $debug = false;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->debug = self::$kernel->isDebug();
    }

    protected function doEcho(string $name, $value, bool $newLine = false): void
    {
        if ($this->debug) {
            $format = "\n%-15s: %s" . ($newLine ? "\n" : '');
            \printf($format, \htmlspecialchars($name), $value);
        }
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
        $this->client->loginUser($user, $firewall);
    }
}
