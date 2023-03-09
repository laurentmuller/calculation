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
use App\Tests\DatabaseTrait;
use App\Tests\ServiceTrait;
use App\Util\RoleBuilder;

use function PHPUnit\Framework\throwException;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Abstract class for authenticate user.
 */
abstract class AbstractAuthenticateWebTestCase extends WebTestCase
{
    use DatabaseTrait;
    use ServiceTrait;

    final public const ID_ADMIN = 2;
    final public const ID_DISABLE = 4;
    final public const ID_SUPER_ADMIN = 1;
    final public const ID_USER = 3;
    final public const ROLE_ADMIN = RoleInterface::ROLE_ADMIN;
    final public const ROLE_DISABLED = 'ROLE_DISABLED';
    final public const ROLE_FAKE = 'ROLE_FAKE';
    final public const ROLE_SUPER_ADMIN = RoleInterface::ROLE_SUPER_ADMIN;
    final public const ROLE_USER = RoleInterface::ROLE_USER;

    protected ?KernelBrowser $client = null;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        // get rights
        $userRight = RoleBuilder::getRoleUser()->getRights();
        $adminRight = RoleBuilder::getRoleAdmin()->getRights();

        $application = $this->getService(ApplicationService::class);
        $application->setProperties([
            PropertyServiceInterface::P_USER_RIGHTS => $userRight,
            PropertyServiceInterface::P_ADMIN_RIGHTS => $adminRight,
            PropertyServiceInterface::P_QR_CODE => true,
        ]);
    }

    /**
     * Checks the given URL.
     *
     * @param string $url      the URL to be tested
     * @param string $username the username to login
     * @param int    $expected the expected result
     *
     * @throws \InvalidArgumentException if the response cannot be found
     */
    protected function checkResponse(string $url, string $username, int $expected): void
    {
        self::assertNotNull($this->client);
        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();
        self::assertSame($expected, $statusCode, "Invalid status code for '$url' and '$username'.");
    }

    protected function doEcho(string $name, mixed $value, bool $newLine = false): void
    {
        $format = "\n%-15s: %s" . ($newLine ? "\n" : '');
        \printf($format, \htmlspecialchars($name), (string) $value);
    }

    /**
     * Loads a user from the database.
     *
     * @param string $username the username to search for
     * @param bool   $verify   true to check if the user is not null
     *
     * @return ?User the user, if found; null otherwise
     *
     * @psalm-return ($verify is true ? User : (User|null))
     */
    protected function loadUser(string $username, bool $verify = true): ?User
    {
        $repository = $this->getService(UserRepository::class);
        $user = $repository->findByUsername($username);
        if ($verify) {
            self::assertNotNull($user, "The user '$username' is null.");
        }

        return $user;
    }

    /**
     * Login the given user.
     */
    protected function loginUser(User $user): void
    {
        $this->client?->loginUser($user);
    }

    /**
     * Login with the given username.
     *
     * @param string $username the username to login
     * @param bool   $verify   true to check if the user is not null
     *
     * @throws \InvalidArgumentException if the given username cannot be found
     */
    protected function loginUsername(string $username, bool $verify = true): void
    {
        $user = $this->loadUser($username, $verify);
        if ($user instanceof User) {
            $this->loginUser($user);
        } else {
            throwException(new \InvalidArgumentException("Unable to find the user '$username'."));
        }
    }
}
