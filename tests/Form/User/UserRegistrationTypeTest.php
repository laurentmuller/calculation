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
use App\Form\Type\CaptchaImageType;
use App\Form\User\AbstractUserCaptchaType;
use App\Form\User\UserRegistrationType;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[CoversClass(AbstractUserCaptchaType::class)]
#[CoversClass(UserRegistrationType::class)]
class UserRegistrationTypeTest extends TypeTestCase
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
        $view = $this->factory->create(UserRegistrationType::class, $user)
            ->createView();

        foreach (\array_keys($data) as $key) {
            self::assertArrayHasKey($key, $view);
            self::assertSame($data[$key], $view->children[$key]->vars['value']);
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
     * @throws Exception|\ReflectionException
     */
    protected function getExtensions(): array
    {
        $extensions = $this->getExtensionsFromTrait();
        $extensions[] = $this->getPasswordHasherExtension();

        return $extensions;
    }

    /**
     * @throws Exception
     */
    protected function getPreloadedExtensions(): array
    {
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $service = $this->createMock(CaptchaImageService::class);
        $service->method('generateImage')
            ->willReturn('fake_content');
        $application = $this->createMock(ApplicationService::class);
        $application->method('isDisplayCaptcha')
            ->willReturn(false);

        $translator = $this->createMockTranslator();
        $type = new UserRegistrationType($service, $application);
        $type->setTranslator($translator);

        return [
            $type,
            new CaptchaImageType($generator),
        ];
    }

    protected function getTypeExtensions(): array
    {
        return [
            new TextTypeExtension(),
        ];
    }
}