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

namespace App\Tests\Web;

use App\Entity\User;
use App\Interfaces\RoleInterface;
use App\Parameter\ApplicationParameters;
use App\Repository\UserRepository;
use App\Service\RoleBuilderService;
use App\Tests\ContainerServiceTrait;
use App\Tests\DatabaseTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

/**
 * Abstract web test case for authenticated user.
 */
abstract class AuthenticateWebTestCase extends WebTestCase
{
    use ContainerServiceTrait;
    use DatabaseTrait;

    public const int ID_ADMIN = 2;
    public const int ID_DISABLE = 4;
    public const int ID_SUPER_ADMIN = 1;
    public const int ID_USER = 3;
    public const string ROLE_ADMIN = RoleInterface::ROLE_ADMIN;
    public const string ROLE_DISABLED = 'ROLE_DISABLED';
    public const string ROLE_FAKE = 'ROLE_FAKE';
    public const string ROLE_SUPER_ADMIN = RoleInterface::ROLE_SUPER_ADMIN;
    public const string ROLE_USER = RoleInterface::ROLE_USER;

    protected KernelBrowser $client;

    #[\Override]
    protected function setUp(): void
    {
        $this->client = static::createClient();
        // get rights
        $builder = new RoleBuilderService();
        $userRight = $builder->getRoleUser()->getRights();
        $adminRight = $builder->getRoleAdmin()->getRights();
        $parameters = $this->getService(ApplicationParameters::class);
        $parameters->getRights()
            ->setAdminRights($adminRight)
            ->setUserRights($userRight);
        $parameters->getSecurity()
            ->setCaptcha(false);
        $parameters->save();
    }

    /**
     * Checks the given URL.
     *
     * @param string $url      the URL to be tested
     * @param string $username the username to login
     * @param int    $expected the expected result
     */
    protected function checkResponse(string $url, string $username, int $expected): string|false
    {
        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();
        self::assertSame($expected, $statusCode, \sprintf("Invalid status code for '%s' and '%s'.", $url, $username));

        return $response->getContent();
    }

    /**
     * Loads a user from the database.
     */
    protected function loadUser(string $username): ?User
    {
        return $this->getService(UserRepository::class)
            ->findByUsername($username);
    }

    /**
     * Log in the given user.
     */
    protected function loginUser(User $user): void
    {
        $this->client->loginUser($user);
    }

    /**
     * Load and login with the given username.
     *
     * @param string $username the username to login
     */
    protected function loginUsername(string $username): void
    {
        $user = $this->loadUser($username);
        self::assertNotNull($user);
        $this->loginUser($user);
    }

    /**
     * Returns if the given username is valid to be log in.
     */
    protected function mustLogin(string $username): bool
    {
        return '' !== $username && AuthenticatedVoter::PUBLIC_ACCESS !== $username;
    }
}
