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

use App\Entity\User;
use App\Form\Type\PlainType;
use App\Form\User\UserRightsType;
use App\Interfaces\RoleInterface;
use App\Service\RoleHierarchyService;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class UserRightsTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use TranslatorMockTrait;

    public function testFormView(): void
    {
        $user = new User();
        $user->setUsername('username')
            ->setRole(RoleInterface::ROLE_ADMIN);

        $data = [
            'username' => 'username',
            'role' => 'user.roles.admin',
            'enabled' => 'common.value_enabled',
            'overwrite' => '1',
        ];
        $view = $this->factory->create(UserRightsType::class, $user)
            ->createView();

        foreach (\array_keys($data) as $key) {
            self::assertArrayHasKey($key, $view);
            self::assertSame($data[$key], $view->children[$key]->vars['value']);
        }
    }

    public function testSubmitRoleAdmin(): void
    {
        $data = [
            'username' => 'username',
            'role' => RoleInterface::ROLE_ADMIN,
            'enabled' => true,
            'overwrite' => false,
        ];

        $form = $this->factory->create(UserRightsType::class);
        $form->submit($data);
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
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

        $translator = $this->createMockTranslator();
        $type = new UserRightsType(false, $service);
        $type->setTranslator($translator);

        return [
            $type,
            new PlainType($translator),
        ];
    }
}
