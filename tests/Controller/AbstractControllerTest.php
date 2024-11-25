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

namespace App\Tests\Controller;

use App\Controller\AbstractController;
use App\Form\FormHelper;
use App\Interfaces\PropertyServiceInterface;
use App\Service\ApplicationService;
use App\Service\UrlGeneratorService;
use App\Service\UserService;
use Faker\Container\ContainerException;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractControllerTest extends KernelTestCase
{
    public function testGetAddressFrom(): void
    {
        $controller = $this->createController();
        $actual = $controller->getAddressFrom();
        self::assertInstanceOf(Address::class, $actual);
    }

    public function testGetApplicationName(): void
    {
        $controller = $this->createController();
        $actual = $controller->getApplicationName();
        self::assertNotEmpty($actual);
    }

    public function testGetApplicationOwnerUrl(): void
    {
        $controller = $this->createController();
        $actual = $controller->getApplicationOwnerUrl();
        self::assertNotEmpty($actual);
    }

    public function testGetApplicationService(): void
    {
        $controller = $this->createController();
        $actual = $controller->getApplicationService();
        self::assertInstanceOf(ApplicationService::class, $actual);
    }

    public function testGetMinMargin(): void
    {
        $controller = $this->createController();
        $actual = $controller->getMinMargin();
        self::assertSame(PropertyServiceInterface::class::DEFAULT_MIN_MARGIN, $actual);
    }

    public function testGetRequestStack(): void
    {
        $controller = $this->createController();
        $actual = $controller->getRequestStack();
        self::assertInstanceOf(RequestStack::class, $actual);
        // second time for test caching
        $actual = $controller->getRequestStack();
        self::assertInstanceOf(RequestStack::class, $actual);
    }

    /**
     * @throws Exception
     */
    public function testGetRequestStackException(): void
    {
        $controller = $this->createMockController();
        self::expectException(\LogicException::class);
        $controller->getRequestStack();
    }

    public function testGetSubscribedServices(): void
    {
        $controller = $this->createController();
        $actual = $controller::getSubscribedServices();
        self::assertContains(UserService::class, $actual);
        self::assertContains(TranslatorInterface::class, $actual);
        self::assertContains(UrlGeneratorService::class, $actual);
    }

    public function testGetTranslator(): void
    {
        $controller = $this->createController();
        $actual = $controller->getTranslator();
        self::assertInstanceOf(TranslatorInterface::class, $actual);
        // second time for test caching
        $actual = $controller->getTranslator();
        self::assertInstanceOf(TranslatorInterface::class, $actual);
    }

    /**
     * @throws Exception
     */
    public function testGetTranslatorException(): void
    {
        $controller = $this->createMockController();
        self::expectException(\LogicException::class);
        $controller->getTranslator();
    }

    public function testGetUrlGenerator(): void
    {
        $controller = $this->createController();
        $actual = $controller->getUrlGenerator();
        self::assertInstanceOf(UrlGeneratorService::class, $actual);
        // second time for test caching
        $actual = $controller->getUrlGenerator();
        self::assertInstanceOf(UrlGeneratorService::class, $actual);
    }

    /**
     * @throws Exception
     */
    public function testGetUrlGeneratorException(): void
    {
        $controller = $this->createMockController();
        self::expectException(\LogicException::class);
        $controller->getUrlGenerator();
    }

    public function testGetUserIdentifier(): void
    {
        $controller = $this->createController();
        $actual = $controller->getUserIdentifier();
        self::assertNull($actual);
    }

    public function testGetUserService(): void
    {
        $controller = $this->createController();
        $actual = $controller->getUserService();
        self::assertInstanceOf(UserService::class, $actual);
        // second time for test caching
        $actual = $controller->getUserService();
        self::assertInstanceOf(UserService::class, $actual);
    }

    public function testRedirectToHomePage(): void
    {
        $controller = $this->createController();
        $actual = $controller->redirectToHomePage();
        self::assertInstanceOf(RedirectResponse::class, $actual);
    }

    public function testRedirectToHomePageWithMessage(): void
    {
        $controller = $this->createController();
        $actual = $controller->redirectToHomePage('id', ['key' => 'value']);
        self::assertInstanceOf(RedirectResponse::class, $actual);
    }

    public function testRedirectToHomePageWithRequest(): void
    {
        $request = new Request();
        $controller = $this->createController();
        $actual = $controller->redirectToHomePage(request: $request);
        self::assertInstanceOf(RedirectResponse::class, $actual);
    }

    private function createController(): AbstractController
    {
        return new class(self::getContainer()) extends AbstractController {
            public function __construct(ContainerInterface $container)
            {
                $this->setContainer($container);
            }

            public function createFormHelper(
                ?string $labelPrefix = null,
                mixed $data = null,
                array $options = []
            ): FormHelper {
                return parent::createFormHelper($labelPrefix, $data, $options);
            }
        };
    }

    /**
     * @throws Exception
     */
    private function createMockController(): AbstractController
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willThrowException(new ContainerException());

        return new class($container) extends AbstractController {
            public function __construct(ContainerInterface $container)
            {
                $this->setContainer($container);
            }
        };
    }
}
