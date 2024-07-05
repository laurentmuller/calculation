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

namespace App\Tests\Form\User;

use App\Form\AbstractHelperType;
use App\Form\Type\PlainType;
use App\Form\User\AttributeRightType;
use App\Form\User\RightsType;
use App\Form\User\RoleRightsType;
use App\Interfaces\RoleInterface;
use App\Service\RoleHierarchyService;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

#[CoversClass(AbstractHelperType::class)]
#[CoversClass(RoleRightsType::class)]
#[CoversClass(AttributeRightType::class)]
#[CoversClass(RightsType::class)]
class RoleRightsTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use TranslatorMockTrait;

    public function testSubmitRoleAdmin(): void
    {
        $data = [
            'role' => RoleInterface::ROLE_ADMIN,
        ];
        $form = $this->factory->create(RoleRightsType::class);
        $form->submit($data);
        self::assertTrue($form->isSynchronized());
    }

    public function testSubmitRoleUser(): void
    {
        $data = [
            'role' => RoleInterface::ROLE_USER,
        ];
        $form = $this->factory->create(RoleRightsType::class);
        $form->submit($data);
        self::assertTrue($form->isSynchronized());
    }

    /**
     * @throws Exception
     */
    protected function getPreloadedExtensions(): array
    {
        $roleHierarchy = $this->createMock(RoleHierarchyInterface::class);
        $roleHierarchy->method('getReachableRoleNames')
            ->willReturn([RoleInterface::ROLE_ADMIN]);
        $service = new RoleHierarchyService($roleHierarchy);

        return [
            new RoleRightsType(false, $service),
            new PlainType($this->createMockTranslator()),
        ];
    }
}
