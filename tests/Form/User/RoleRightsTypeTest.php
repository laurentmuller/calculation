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

use App\Enums\EntityName;
use App\Form\Extension\InputGroupTypeExtension;
use App\Form\Type\PlainType;
use App\Form\User\RightsType;
use App\Form\User\RoleRightsType;
use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Service\EntityNameService;
use App\Service\RoleService;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

#[AllowMockObjectsWithoutExpectations]
final class RoleRightsTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use TranslatorMockTrait;

    public function testSubmitRoleAdmin(): void
    {
        $this->submitRole(RoleInterface::ROLE_ADMIN);
    }

    public function testSubmitRoleUser(): void
    {
        $this->submitRole(RoleInterface::ROLE_USER);
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        $service = $this->createMock(EntityNameService::class);
        $service->method('getEntities')
            ->willReturn(EntityName::sorted());
        $rightsType = new RightsType($service);
        $roleHierarchy = $this->createMock(RoleHierarchyInterface::class);
        $roleHierarchy->method('getReachableRoleNames')
            ->willReturn([RoleInterface::ROLE_ADMIN]);
        $translator = $this->createMockTranslator();
        $service = new RoleService($roleHierarchy, $translator);
        $roleRightsType = new RoleRightsType($service);

        return [
            $rightsType,
            $roleRightsType,
            new PlainType($this->createMockTranslator()),
        ];
    }

    /**
     * @return InputGroupTypeExtension[]
     */
    #[\Override]
    protected function getTypeExtensions(): array
    {
        return [new InputGroupTypeExtension()];
    }

    /**
     * @phpstan-param RoleInterface::ROLE_* $role
     */
    private function submitRole(string $role): void
    {
        $role = new Role($role);
        $data = ['role' => $role->getRole()];
        $form = $this->factory->create(RoleRightsType::class, $role);
        $form->submit($data);
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }
}
