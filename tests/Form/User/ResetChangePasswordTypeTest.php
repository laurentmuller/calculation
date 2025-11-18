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
use App\Form\Type\CaptchaImageType;
use App\Form\User\ResetChangePasswordType;
use App\Parameter\ApplicationParameters;
use App\Parameter\SecurityParameter;
use App\Service\CaptchaImageService;
use App\Tests\Entity\IdTrait;
use App\Tests\Form\PreloadedExtensionsTrait;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ResetChangePasswordTypeTest extends TypeTestCase
{
    use IdTrait;
    use PasswordHasherExtensionTrait;
    use PreloadedExtensionsTrait {
        getExtensions as getExtensionsFromTrait;
    }
    use ValidatorExtensionTrait;

    private User $user;

    /**
     * @throws \ReflectionException
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setUsername('username')
            ->setEmail('email@email.com');
        self::setId($this->user);
        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        $form = $this->factory->create(ResetChangePasswordType::class);
        $form->submit([$this->user->getId()]);
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    /**
     * @throws \ReflectionException
     */
    #[\Override]
    protected function getExtensions(): array
    {
        $extensions = $this->getExtensionsFromTrait();
        $extensions[] = $this->getPasswordHasherExtension();

        return $extensions;
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $service = $this->createMock(CaptchaImageService::class);
        $service->method('generateImage')
            ->willReturn('fake_content');
        $security = $this->createMock(SecurityParameter::class);
        $security->method('isCaptcha')
            ->willReturn(false);
        $parameters = $this->createMock(ApplicationParameters::class);
        $parameters->method('getSecurity')
            ->willReturn($security);

        return [
            new CaptchaImageType($generator),
            new ResetChangePasswordType($service, $parameters),
        ];
    }
}
