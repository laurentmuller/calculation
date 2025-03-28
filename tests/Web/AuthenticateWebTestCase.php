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
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Repository\UserRepository;
use App\Service\ApplicationService;
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

    final public const ID_ADMIN = 2;
    final public const ID_DISABLE = 4;
    final public const ID_SUPER_ADMIN = 1;
    final public const ID_USER = 3;
    final public const ROLE_ADMIN = RoleInterface::ROLE_ADMIN;
    final public const ROLE_DISABLED = 'ROLE_DISABLED';
    final public const ROLE_FAKE = 'ROLE_FAKE';
    final public const ROLE_SUPER_ADMIN = RoleInterface::ROLE_SUPER_ADMIN;
    final public const ROLE_USER = RoleInterface::ROLE_USER;

    protected KernelBrowser $client;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        // get rights
        $builder = new RoleBuilderService();
        $userRight = $builder->getRoleUser()->getRights();
        $adminRight = $builder->getRoleAdmin()->getRights();

        $application = $this->getService(ApplicationService::class);
        $application->setProperties([
            PropertyServiceInterface::P_USER_RIGHTS => $userRight,
            PropertyServiceInterface::P_ADMIN_RIGHTS => $adminRight,
            PropertyServiceInterface::P_DISPLAY_CAPTCHA => false,
        ]);
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
        self::assertSame($expected, $statusCode, "Invalid status code for '$url' and '$username'.");

        return $response->getContent();
    }

    /**
     * Loads a user from the database.
     */
    protected function loadUser(string $username): ?User
    {
        $repository = $this->getService(UserRepository::class);

        return $repository->findByUsername($username);
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
