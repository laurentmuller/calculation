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

namespace App\Tests\Form\Admin;

use App\Entity\User;
use App\Enums\TableView;
use App\Form\Admin\ApplicationParametersType;
use App\Form\CalculationState\CalculationStateListType;
use App\Form\Extension\TextTypeExtension;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Service\ApplicationService;
use App\Tests\Form\CalculationState\CalculationStateTrait;
use App\Tests\Form\Category\CategoryTrait;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\Form\Product\ProductTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Test\TypeTestCase;

class ApplicationParametersTypeTest extends TypeTestCase
{
    use CalculationStateTrait;
    use CategoryTrait;
    use PreloadedExtensionsTrait;
    use ProductTrait;
    use TranslatorMockTrait;

    private MockObject&Security $security;
    private ?User $user = null;

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
        $form = $this->factory->create(ApplicationParametersType::class, []);
        $form->submit([]);
        self::assertTrue($form->isSynchronized());
    }

    public function testSubmitSynchronizedSuperAdmin(): void
    {
        $this->user = new User();
        $this->user->setRole(RoleInterface::ROLE_SUPER_ADMIN);
        $form = $this->factory->create(ApplicationParametersType::class, []);
        $form->submit([]);
        self::assertTrue($form->isSynchronized());
    }

    /**
     * @throws \ReflectionException
     */
    protected function getPreloadedExtensions(): array
    {
        $translator = $this->createMockTranslator();
        $service = $this->createMock(ApplicationService::class);
        $service->method('getDefaultValues')
            ->willReturn([
                PropertyServiceInterface::P_DISPLAY_MODE => TableView::CUSTOM,
                PropertyServiceInterface::P_MESSAGE_CLOSE => false,
            ]);

        return [
            new ApplicationParametersType($this->security, $translator, $service),
            new CalculationStateListType($translator),
            $this->getProductEntityType(),
            $this->getCategoryEntityType(),
            $this->getCalculationStateEntityType(),
        ];
    }

    /**
     * @return TextTypeExtension[]
     */
    protected function getTypeExtensions(): array
    {
        return [
            new TextTypeExtension(),
        ];
    }
}
