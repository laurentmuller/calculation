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

namespace App\Tests\Parameter;

use App\Entity\User;
use App\Entity\UserProperty;
use App\Enums\EntityAction;
use App\Parameter\ApplicationParameters;
use App\Parameter\UserParameters;
use App\Repository\GlobalPropertyRepository;
use App\Repository\UserPropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class UserParametersTest extends TestCase
{
    public function testGetDefaultValues(): void
    {
        $user = $this->createUser();
        $parameters = $this->createUserParameters(user: $user);
        $actual = $parameters->getDefaultValues();
        self::assertNotEmpty($actual);
    }

    public function testSaveNoUser(): void
    {
        $cache = new ArrayAdapter();
        $manager = $this->createMockManager();
        $security = $this->createMockSecurity();
        $application = $this->createApplication();
        $parameters = new UserParameters(
            $cache,
            $manager,
            $security,
            $application
        );
        $parameters->getDisplay()
            ->setEditAction(EntityAction::NONE);

        self::expectException(\LogicException::class);
        $parameters->save();
    }

    public function testSaveSuccess(): void
    {
        $user = $this->createUser();
        $parameters = $this->createUserParameters(user: $user);
        $parameters->getDisplay()
            ->setEditAction(EntityAction::NONE);
        $parameters->getHomePage()
            ->setDarkNavigation(true);
        $parameters->getMessage()
            ->setIcon(false);
        $parameters->getOptions()
            ->setPrintAddress(true);

        $actual = $parameters->save();
        self::assertTrue($actual);
    }

    public function testSaveWithProperty(): void
    {
        $user = $this->createUser();
        $property = UserProperty::instance('fake', $user);
        $parameters = $this->createUserParameters([$property], $user);
        $parameters->getOptions()
            ->setPrintAddress(true);

        $actual = $parameters->save();
        self::assertTrue($actual);
    }

    private function createApplication(): ApplicationParameters
    {
        $cache = new ArrayAdapter();
        $repository = $this->createMock(GlobalPropertyRepository::class);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')
            ->willReturn($repository);

        return new ApplicationParameters($cache, $manager, false);
    }

    private function createMockManager(array $properties = []): MockObject&EntityManagerInterface
    {
        $repository = $this->createMock(UserPropertyRepository::class);
        $repository->method('findByUser')
            ->willReturn($properties);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')
            ->willReturn($repository);

        return $manager;
    }

    private function createMockSecurity(?User $user = null): MockObject&Security
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')
            ->willReturn($user);

        return $security;
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setUsername('fake');

        return $user;
    }

    private function createUserParameters(array $properties = [], ?User $user = null): UserParameters
    {
        $cache = new ArrayAdapter();
        $manager = $this->createMockManager($properties);
        $security = $this->createMockSecurity($user);
        $application = $this->createApplication();

        return new UserParameters(
            $cache,
            $manager,
            $security,
            $application
        );
    }
}
