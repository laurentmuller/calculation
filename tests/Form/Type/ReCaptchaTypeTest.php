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

use App\Form\Type\ReCaptchaType;
use App\Service\RecaptchaService;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ReCaptcha\Response;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ReCaptchaTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use TranslatorMockTrait;

    private ?Request $request = null;
    private MockObject&RequestStack $requestStack;
    private MockObject&RecaptchaService $service;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->service = $this->createService();
        $this->request = $this->createMock(Request::class);
        $this->requestStack = $this->createRequestStack();

        parent::setUp();
    }

    public function testFormView(): void
    {
        $view = $this->factory->create(ReCaptchaType::class)
            ->createView();

        self::assertArrayHasKey('recaptcha_url', $view->vars);
        self::assertIsString($view->vars['recaptcha_url']);
        self::assertStringStartsWith('https://www.google.com/recaptcha/api.js?render=', $view->vars['recaptcha_url']);

        self::assertArrayHasKey('attr', $view->vars);
        $attr = $view->vars['attr'];
        self::assertArrayHasKey('data-key', $attr);

        self::assertArrayHasKey('class', $attr);
        self::assertSame('recaptcha', $attr['class']);

        self::assertArrayHasKey('data-event', $attr);
        self::assertSame('click', $attr['data-event']);

        self::assertArrayHasKey('data-selector', $attr);
        self::assertSame('[data-toggle="recaptcha"]', $attr['data-selector']);

        self::assertArrayHasKey('data-action', $attr);
        self::assertSame('login', $attr['data-action']);
    }

    public function testSubmitError(): void
    {
        $data = 'test';
        $error = 'action-mismatch';
        $this->setRequest($this->request);
        $this->setResponse($error);
        $this->service->method('translateErrors')
            ->willReturn([$error]);

        $form = $this->factory->create(ReCaptchaType::class, $data);
        $form->submit($data);
        self::assertTrue($form->isSynchronized());
        self::assertSame($data, $form->getData());

        self::assertCount(1, $form->getErrors());
        $errorForm = $form->getErrors()[0];
        self::assertSame($error, $errorForm->getMessage()); // @phpstan-ignore method.notFound
    }

    public function testSubmitNoRequest(): void
    {
        $data = 'test';
        $this->setRequest(null);
        $this->setResponse();

        $this->service->method('translateError')
            ->willReturnArgument(0);

        $form = $this->factory->create(ReCaptchaType::class, $data);
        $form->submit($data);
        self::assertTrue($form->isSynchronized());
        self::assertSame($data, $form->getData());

        self::assertCount(1, $form->getErrors());
        $errorForm = $form->getErrors()[0];
        self::assertSame('no-request', $errorForm->getMessage()); // @phpstan-ignore method.notFound
    }

    public function testSubmitSuccess(): void
    {
        $data = 'test';
        $this->setRequest($this->request);
        $this->setResponse();

        $form = $this->factory->create(ReCaptchaType::class, $data);
        $form->submit($data);
        self::assertTrue($form->isSynchronized());
        self::assertCount(0, $form->getErrors());
        self::assertSame($data, $form->getData());
    }

    protected function getPreloadedExtensions(): array
    {
        return [
            new ReCaptchaType($this->service, $this->requestStack),
        ];
    }

    /**
     * @throws Exception
     */
    private function createRequestStack(): MockObject&RequestStack
    {
        return $this->createMock(RequestStack::class);
    }

    /**
     * @throws Exception
     */
    private function createService(): MockObject&RecaptchaService
    {
        $service = $this->createMock(RecaptchaService::class);
        $service->method('getExpectedAction')
            ->willReturn('login');

        return $service;
    }

    private function setRequest(?Request $request): void
    {
        $this->requestStack->method('getCurrentRequest')
            ->willReturn($request);
    }

    private function setResponse(string $code = ''): void
    {
        $success = '' === $code;
        $errorCodes = $success ? [] : [$code];
        $response = new Response($success, $errorCodes);
        $this->service->method('verify')
            ->willReturn($response);
    }
}
