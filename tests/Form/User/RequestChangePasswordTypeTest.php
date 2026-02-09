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

use App\Form\Extension\InputGroupTypeExtension;
use App\Form\Type\CaptchaImageType;
use App\Form\Type\CurrentPasswordType;
use App\Form\User\RequestChangePasswordType;
use App\Parameter\ApplicationParameters;
use App\Parameter\SecurityParameter;
use App\Service\CaptchaImageService;
use App\Tests\Form\PreloadedExtensionsTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AllowMockObjectsWithoutExpectations]
final class RequestChangePasswordTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use ValidatorExtensionTrait;

    public function testFormView(): void
    {
        $data = [
            'user' => 'username',
        ];
        $children = $this->factory
            ->create(RequestChangePasswordType::class, $data)
            ->createView()
            ->children;

        foreach (\array_keys($data) as $key) {
            self::assertArrayHasKey($key, $children);
            self::assertSame($data[$key], $children[$key]->vars['value']);
        }
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'username' => 'username',
            'password' => 'password',
            'remember_me' => true,
        ];
        $form = $this->factory->create(RequestChangePasswordType::class);
        $form->submit($formData);
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        $service = $this->createMock(CaptchaImageService::class);
        $service->method('generateImage')
            ->willReturn('fake_content');
        $security = $this->createMock(SecurityParameter::class);
        $security->method('isCaptcha')
            ->willReturn(true);
        $parameters = $this->createMock(ApplicationParameters::class);
        $parameters->method('getSecurity')
            ->willReturn($security);

        return [
            new CurrentPasswordType(),
            new CaptchaImageType(self::createStub(UrlGeneratorInterface::class)),
            new RequestChangePasswordType($service, $parameters),
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
