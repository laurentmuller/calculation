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
use App\Form\AbstractChoiceType;
use App\Form\User\RoleChoiceType;
use App\Interfaces\RoleInterface;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(AbstractChoiceType::class)]
#[CoversClass(RoleChoiceType::class)]
class RoleChoiceTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use TranslatorMockTrait;

    public function testSubmitSynchronized(): void
    {
        $form = $this->factory->create(RoleChoiceType::class, RoleInterface::ROLE_USER);
        $form->submit(RoleInterface::ROLE_USER);
        self::assertTrue($form->isSynchronized());
    }

    /**
     * @throws Exception
     */
    protected function getPreloadedExtensions(): array
    {
        $user = new User();
        $user->setRole(RoleInterface::ROLE_SUPER_ADMIN);
        $security = $this->createMock(Security::class);
        $security->method('getUser')
            ->willReturn($user);

        return [
            new RoleChoiceType($security),
        ];
    }
}