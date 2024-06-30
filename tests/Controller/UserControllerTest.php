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

namespace App\Tests\Controller;

use App\Controller\AbstractController;
use App\Controller\AbstractEntityController;
use App\Controller\UserController;
use App\Entity\User;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(AbstractController::class)]
#[CoversClass(AbstractEntityController::class)]
#[CoversClass(UserController::class)]
class UserControllerTest extends EntityControllerTestCase
{
    private ?User $user = null;

    public static function getRoutes(): \Iterator
    {
        yield ['/user', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user', self::ROLE_ADMIN];
        yield ['/user', self::ROLE_SUPER_ADMIN];
        yield ['/user', self::ROLE_DISABLED, Response::HTTP_FORBIDDEN];

        yield ['/user/add', self::ROLE_ADMIN];
        yield ['/user/add', self::ROLE_SUPER_ADMIN];
        yield ['/user/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];

        yield ['/user/edit/1', self::ROLE_ADMIN];
        yield ['/user/edit/1', self::ROLE_SUPER_ADMIN];

        yield ['/user/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/delete/1', self::ROLE_ADMIN];

        // can delete when connected
        yield ['/user/delete/1', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND];
        yield ['/user/delete/2', self::ROLE_SUPER_ADMIN];

        yield ['/user/show/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/show/1', self::ROLE_ADMIN];
        yield ['/user/show/1', self::ROLE_SUPER_ADMIN];

        yield ['/user/password/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/password/1', self::ROLE_ADMIN];
        yield ['/user/password/1', self::ROLE_SUPER_ADMIN];

        yield ['/user/reset', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/reset', self::ROLE_ADMIN, Response::HTTP_FOUND];
        yield ['/user/reset', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND];

        yield ['/user/reset/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/reset/1', self::ROLE_ADMIN];
        yield ['/user/reset/1', self::ROLE_SUPER_ADMIN];

        yield ['/user/rights/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/rights/1', self::ROLE_ADMIN];
        yield ['/user/rights/1', self::ROLE_SUPER_ADMIN];

        yield ['/user/rights/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/rights/pdf', self::ROLE_ADMIN];
        yield ['/user/rights/pdf', self::ROLE_SUPER_ADMIN];

        yield ['/user/rights/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/rights/excel', self::ROLE_ADMIN];
        yield ['/user/rights/excel', self::ROLE_SUPER_ADMIN];

        yield ['/user/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/pdf', self::ROLE_ADMIN];
        yield ['/user/pdf', self::ROLE_SUPER_ADMIN];

        yield ['/user/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/excel', self::ROLE_ADMIN];
        yield ['/user/excel', self::ROLE_SUPER_ADMIN];

        yield ['/user/parameters', self::ROLE_USER];
        yield ['/user/parameters', self::ROLE_ADMIN];
        yield ['/user/parameters', self::ROLE_SUPER_ADMIN];
    }

    public function testMessageToSameUser(): void
    {
        $uri = \sprintf('/user/message/%d', self::ID_ADMIN);
        $this->loginUsername(self::ROLE_ADMIN);
        $this->client->request(
            method: Request::METHOD_GET,
            uri: $uri
        );
        $this->checkResponse($uri, self::ROLE_ADMIN, Response::HTTP_FOUND);
    }

    public function testPassword(): void
    {
        $uri = \sprintf('/user/password/%d', self::ID_ADMIN);
        $data = [
            'plainPassword[first]' => '$password123456#',
            'plainPassword[second]' => '$password123456#',
        ];
        $this->checkEditEntity($uri, $data, id: 'common.button_ok');
    }

    public function testResetNotResettable(): void
    {
        $uri = \sprintf('/user/reset/%d', self::ID_ADMIN);
        $this->checkEditEntity($uri, id: 'common.button_delete');
    }

    /**
     * @throws ORMException
     */
    public function testResetSuccess(): void
    {
        $expiresAt = \DateTimeImmutable::createFromInterface(new \DateTime());
        $user = $this->getUser();
        $user->setResetPasswordRequest($expiresAt, 'selector', 'hashedToken');
        $this->addEntity($user);

        $uri = \sprintf('/user/reset/%d', (int) $user->getId());
        $this->checkEditEntity($uri, id: 'common.button_delete');
    }

    /**
     * @throws ORMException
     */
    protected function deleteUser(): void
    {
        if ($this->user instanceof User) {
            $this->user = $this->deleteEntity($this->user);
        }
    }

    /**
     * @throws ORMException
     */
    private function getUser(): User
    {
        if ($this->user instanceof User) {
            return $this->user;
        }

        $this->user = new User();
        $this->user->setUsername('Test User')
            ->setEmail('example@example.com')
            ->setPassword('password');

        return $this->addEntity($this->user);
    }
}
