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

namespace App\Tests\Form\Type;

use App\Captcha\AlphaCaptchaInterface;
use App\Captcha\Challenge;
use App\Form\Type\AlphaCaptchaType;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Validator\Validation;

class AlphaCaptchaTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use TranslatorMockTrait;
    use ValidatorExtensionTrait;

    private bool $valid = true;

    public function testFormView(): void
    {
        $this->valid = true;
        $view = $this->factory->create(AlphaCaptchaType::class)
            ->createView();
        self::assertArrayHasKey('id', $view->vars);
        self::assertArrayHasKey('question', $view->vars);
        self::assertSame('question', $view->vars['question']);
    }

    public function testSubmitInvalid(): void
    {
        $this->validateSubmit(false);
    }

    public function testSubmitValid(): void
    {
        $this->validateSubmit(true);
    }

    /**
     * @throws Exception
     */
    protected function getPreloadedExtensions(): array
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('alpha_captcha_answer', 'fake');

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')
            ->willReturn($session);

        $challenge = new Challenge('question', 'nextAnswer');
        $alphaCaptcha = $this->createMock(AlphaCaptchaInterface::class);
        $alphaCaptcha->method('getChallenge')
            ->willReturn($challenge);
        $alphaCaptcha->method('checkAnswer')
            ->willReturnCallback(fn (): bool => $this->valid);

        $translator = $this->createMockTranslator();
        $captchaType = new AlphaCaptchaType([$alphaCaptcha], $translator);
        $captchaType->setRequestStack($requestStack);

        return [$captchaType];
    }

    protected function getValidatorExtension(): ValidatorExtension
    {
        $validator = Validation::createValidator();

        return new ValidatorExtension($validator);
    }

    private function validateSubmit(bool $valid): void
    {
        $this->valid = $valid;
        $form = $this->factory->create(AlphaCaptchaType::class);
        $form->submit('nextAnswer');
        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isSynchronized());
        self::assertSame($valid, $form->isValid());
    }
}
