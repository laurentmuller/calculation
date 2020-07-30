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
use App\Interfaces\ApplicationServiceInterface;
use App\Interfaces\RoleInterface;
use App\Repository\UserRepository;
use App\Security\EntityVoter;
use App\Service\ApplicationService;
use App\Tests\DatabaseTrait;
use App\Tests\LogErrorTrait;
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
    use LogErrorTrait;

    public const ID_ADMIN = 2;
    public const ID_DISABLE = 4;
    public const ID_SUPER_ADMIN = 1;
    public const ID_USER = 3;

    public const ROLE_ADMIN = RoleInterface::ROLE_ADMIN;
    public const ROLE_DISABLED = 'ROLE_DISABLED';
    public const ROLE_FAKE = 'ROLE_FAKE';
    public const ROLE_SUPER_ADMIN = RoleInterface::ROLE_SUPER_ADMIN;
    public const ROLE_USER = RoleInterface::ROLE_USER;

    /**
     * @var KernelBrowser
     */
    protected $client = null;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        // get rights
        $userRight = EntityVoter::getRoleUser()->getRights();
        $adminRight = EntityVoter::getRoleAdmin()->getRights();

        /** @var ApplicationService $application */
        $application = self::$container->get(ApplicationService::class);
        $application->setProperties([
            ApplicationServiceInterface::USER_RIGHTS => $userRight,
            ApplicationServiceInterface::ADMIN_RIGHTS => $adminRight,
        ]);
    }

    protected function checkResponse(string $url, string $username, int $expected): void
    {
        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();
        $this->assertSame($expected, $statusCode, "Invalid status code for '{$url}' and '{$username}'.");
    }

    protected function doEcho(string $name, $value, bool $newLine = false): void
    {
        $format = "\n%-15s: %s" . ($newLine ? "\n" : '');
        \printf($format, \htmlspecialchars($name), $value);
    }

    protected function loadUser(string $username, bool $verify = true): ?User
    {
        /** @var UserRepository $repository */
        $repository = self::$container->get(UserRepository::class);
        $this->assertNotNull($repository, 'The user respository is null.');

        /** @var User $user */
        $user = $repository->findByUsername($username);

        if ($verify) {
            $this->assertNotNull($user, "The user '$username' is null.");
        }

        return $user;
    }

    protected function loginUser(User $user, string $firewall = 'main'): void
    {
        $this->client->loginUser($user, $firewall);
    }

    protected function loginUserName(string $username, bool $verify = true, string $firewall = 'main'): User
    {
        $user = $this->loadUser($username, $verify);
        $this->loginUser($user, $firewall);

        return $user;
    }
}
