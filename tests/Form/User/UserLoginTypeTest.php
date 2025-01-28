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

use App\Form\Extension\TextTypeExtension;
use App\Form\Type\CaptchaImageType;
use App\Form\Type\CurrentPasswordType;
use App\Form\User\UserLoginType;
use App\Service\ApplicationService;
use App\Service\CaptchaImageService;
use App\Tests\Form\PreloadedExtensionsTrait;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserLoginTypeTest extends TypeTestCase
{
    use PasswordHasherExtensionTrait;
    use PreloadedExtensionsTrait;
    use ValidatorExtensionTrait;

    public function testSubmitValidData(): void
    {
        $formData = [
            'username' => 'username',
            'password' => 'password',
            'remember_me' => true,
        ];
        $form = $this->factory->create(UserLoginType::class);
        $form->submit($formData);
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
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

        return [
            new CurrentPasswordType(),
            new CaptchaImageType($generator),
            new UserLoginType($service, $application),
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
