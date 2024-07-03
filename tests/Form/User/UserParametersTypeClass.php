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
use App\Form\Extension\TextTypeExtension;
use App\Form\Parameters\AbstractParametersType;
use App\Form\User\UserParametersType;
use App\Interfaces\RoleInterface;
use App\Service\ApplicationService;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(AbstractParametersType::class)]
#[CoversClass(UserParametersType::class)]
class UserParametersTypeClass extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use TranslatorMockTrait;

    private MockObject&Security $security;
    private ?User $user = null;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->security->method('getUser')
            ->willReturnCallback(fn (): ?User => $this->user);
        parent::setUp();
    }

    public function testSubmitSynchronizedNoUser(): void
    {
        $this->user = null;
        $form = $this->factory->create(UserParametersType::class, []);
        $form->submit([]);
        self::assertTrue($form->isSynchronized());
    }

    public function testSubmitSynchronizedSuperAdmin(): void
    {
        $this->user = new User();
        $this->user->setRole(RoleInterface::ROLE_SUPER_ADMIN);
        $form = $this->factory->create(UserParametersType::class, []);
        $form->submit([]);
        self::assertTrue($form->isSynchronized());
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getPreloadedExtensions(): array
    {
        $translator = $this->createMockTranslator();
        $service = $this->createMock(ApplicationService::class);

        return [
            new UserParametersType($this->security, $translator, $service),
        ];
    }

    protected function getTypeExtensions(): array
    {
        return [
            new TextTypeExtension(),
        ];
    }
}
