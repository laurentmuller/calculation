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

namespace App\Tests\Traits;

use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Traits\AuthorizationCheckerAwareTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class AuthorizationCheckerAwareTraitTest extends TestCase implements ServiceSubscriberInterface
{
    use AuthorizationCheckerAwareTrait;

    public ContainerInterface $container;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->setChecker($this->createMock(AuthorizationCheckerInterface::class));
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public function testIsGranted(): void
    {
        $actual = $this->isGranted(EntityPermission::ADD, EntityName::CALCULATION);
        self::assertFalse($actual);
    }

    public function testIsGrantedAdd(): void
    {
        $actual = $this->isGrantedAdd(EntityName::CALCULATION);
        self::assertFalse($actual);
    }

    public function testIsGrantedDelete(): void
    {
        $actual = $this->isGrantedDelete(EntityName::CALCULATION);
        self::assertFalse($actual);
    }

    public function testIsGrantedEdit(): void
    {
        $actual = $this->isGrantedEdit(EntityName::CALCULATION);
        self::assertFalse($actual);
    }

    public function testIsGrantedExport(): void
    {
        $actual = $this->isGrantedExport(EntityName::CALCULATION);
        self::assertFalse($actual);
    }

    public function testIsGrantedList(): void
    {
        $actual = $this->isGrantedList(EntityName::CALCULATION);
        self::assertFalse($actual);
    }

    public function testIsGrantedShow(): void
    {
        $actual = $this->isGrantedShow(EntityName::CALCULATION);
        self::assertFalse($actual);
    }
}
