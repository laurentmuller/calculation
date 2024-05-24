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
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[CoversClass(AuthorizationCheckerAwareTrait::class)]
class AuthorizationCheckerAwareTraitTest extends AwareTraitTestCase
{
    use AuthorizationCheckerAwareTrait;

    /**
     * @throws ContainerExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();
        $checker = $this->getService(AuthorizationCheckerInterface::class);
        $this->setChecker($checker);
    }

    public function testIsGranted(): void
    {
        $actual = $this->isGranted(EntityPermission::ADD, EntityName::CALCULATION);
        self::assertFalse($actual);
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
