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
use App\Form\Extension\InputGroupTypeExtension;
use App\Form\Type\CaptchaImageType;
use App\Form\User\UserRegistrationType;
use App\Parameter\ApplicationParameters;
use App\Parameter\SecurityParameter;
use App\Service\CaptchaImageService;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UserRegistrationTypeTest extends TypeTestCase
{
    use PasswordHasherExtensionTrait;
    use PreloadedExtensionsTrait {
        getExtensions as getExtensionsFromTrait;
    }
    use TranslatorMockTrait;
    use ValidatorExtensionTrait;

    public function testFormView(): void
    {
        $user = new User();
        $user->setUsername('username')
            ->setEmail('email@email.com');
        $data = [
            'username' => 'username',
            'email' => 'email@email.com',
        ];
        $children = $this->factory
            ->create(UserRegistrationType::class, $user)
            ->createView()
            ->children;

        foreach (\array_keys($data) as $key) {
            self::assertArrayHasKey($key, $children);
            self::assertSame($data[$key], $children[$key]->vars['value']);
        }
    }

    public function testSubmitValidData(): void
    {
        $user = new User();
        $data = [
            'username' => 'username',
            'email' => 'email@email.com',
            'plainPassword' => 'password',
        ];
        $form = $this->factory->create(UserRegistrationType::class, $user);
        $form->submit($data);
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
        $translator = $this->createMockTranslator();

        return [
            new UserRegistrationType($service, $parameters, $translator),
            new CaptchaImageType($generator),
        ];
    }

    /**
     * @return InputGroupTypeExtension[]
     */
    #[\Override]
    protected function getTypeExtensions(): array
    {
        return [
            new InputGroupTypeExtension(),
        ];
    }
}
