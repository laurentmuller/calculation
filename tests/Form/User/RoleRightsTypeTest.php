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
use App\Form\DataTransformer\RightsTransformer;
use App\Form\Extension\InputGroupTypeExtension;
use App\Form\Type\PlainType;
use App\Form\User\RightsType;
use App\Form\User\RoleRightsType;
use App\Interfaces\RoleInterface;
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
        $transformer = self::createStub(RightsTransformer::class);
        $roleRightsType = new RoleRightsType($service, $transformer);

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
}
