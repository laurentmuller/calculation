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
use App\Form\Type\AlphaCaptchaType;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class AlphaCaptchaTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use TranslatorMockTrait;
    use ValidatorExtensionTrait;

    public function testFormView(): void
    {
        $view = $this->factory->create(AlphaCaptchaType::class)
            ->createView();
        self::assertArrayHasKey('id', $view->vars);
        self::assertArrayHasKey('question', $view->vars);
        self::assertSame('question', $view->vars['question']);
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(AlphaCaptchaType::class);
        $form->submit('Test');
        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());
    }

    /**
     * @throws Exception
     */
    protected function getPreloadedExtensions(): array
    {
        $session = new Session(new MockArraySessionStorage());
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')
            ->willReturn($session);

        $alphaCaptcha = $this->createMock(AlphaCaptchaInterface::class);
        $alphaCaptcha->method('getChallenge')
            ->willReturn(['question', 'nextAnswer']);
        $alphaCaptcha->method('checkAnswer')
            ->willReturn(true);

        $translator = $this->createMockTranslator();
        $captchaType = new AlphaCaptchaType([$alphaCaptcha], $translator);
        $captchaType->setRequestStack($requestStack);

        return [
            $captchaType,
        ];
    }
}
