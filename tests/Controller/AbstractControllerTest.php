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
use App\Model\TranslatableFlashMessage;
use App\Parameter\UserParameters;
use App\Report\AbstractReport;
use App\Service\UrlGeneratorService;
use App\Spreadsheet\AbstractDocument;
use App\Tests\DatabaseTrait;
use App\Tests\Fixture\FixtureController;
use App\Word\AbstractWordDocument;
use Faker\Container\ContainerException;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AbstractControllerTest extends KernelTestCase
{
    use DatabaseTrait;

    public function testGetAddressFrom(): void
    {
        $controller = $this->createController();
        $actual = $controller->getAddressFrom();
        self::assertSame('Calculation', $actual->getName());
        self::assertSame('calculation@bibi.nu', $actual->getAddress());
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

    public function testGetApplicationParameters(): void
    {
        $controller = $this->createController();
        $parameters = $controller->getApplicationParameters();
        $actual = $parameters->getMinMargin();
        self::assertSame(1.1, $actual);
    }

    public function testGetMinMargin(): void
    {
        $controller = $this->createController();
        $actual = $controller->getMinMargin();
        self::assertSame(1.1, $actual);
    }

    public function testGetRelativePathFound(): void
    {
        $expected = 'tests/Controller';
        $controller = $this->createController();
        $actual = $controller->getRelativePath(__DIR__);
        self::assertSame($expected, $actual);
    }

    public function testGetRelativePathNotFound(): void
    {
        $path = 'fake_dir/fake.txt';
        $controller = $this->createController();
        $actual = $controller->getRelativePath($path);
        self::assertSame($path, $actual);
    }

    public function testGetRequestStack(): void
    {
        $controller = $this->createController();
        $actual = $controller->getRequestStack();
        $this->assertSameClass(RequestStack::class, $actual);

        // second time for test caching
        $actual = $controller->getRequestStack();
        $this->assertSameClass(RequestStack::class, $actual);
    }

    public function testGetRequestStackException(): void
    {
        $controller = $this->createMockController();
        self::expectException(\LogicException::class);
        $controller->getRequestStack();
    }

    public function testGetRequestStackWithException(): void
    {
        self::expectException(\LogicException::class);
        $controller = $this->createMockController();
        $controller->getRequestStack();
    }

    public function testGetSubscribedServices(): void
    {
        $controller = $this->createController();
        $actual = $controller::getSubscribedServices();
        self::assertContains(UserParameters::class, $actual);
        self::assertContains(TranslatorInterface::class, $actual);
        self::assertContains(UrlGeneratorService::class, $actual);
    }

    public function testGetTranslator(): void
    {
        $controller = $this->createController();
        $actual = $controller->getTranslator();
        $this->assertSameClass(TranslatorInterface::class, $actual);

        // second time for test caching
        $actual = $controller->getTranslator();
        $this->assertSameClass(TranslatorInterface::class, $actual);
    }

    public function testGetTranslatorException(): void
    {
        $controller = $this->createMockController();
        self::expectException(\LogicException::class);
        $controller->getTranslator();
    }

    public function testGetTranslatorWithException(): void
    {
        self::expectException(\LogicException::class);
        $controller = $this->createMockController();
        $controller->getTranslator();
    }

    public function testGetUrlGenerator(): void
    {
        $controller = $this->createController();
        $actual = $controller->getUrlGenerator();
        $this->assertSameClass(UrlGeneratorService::class, $actual);

        // second time for test caching
        $actual = $controller->getUrlGenerator();
        $this->assertSameClass(UrlGeneratorService::class, $actual);
    }

    public function testGetUrlGeneratorException(): void
    {
        $controller = $this->createMockController();
        self::expectException(\LogicException::class);
        $controller->getUrlGenerator();
    }

    public function testGetUrlGeneratorWithException(): void
    {
        self::expectException(\LogicException::class);
        $controller = $this->createMockController();
        $controller->getUrlGenerator();
    }

    public function testGetUserIdentifier(): void
    {
        $controller = $this->createController();
        $actual = $controller->getUserIdentifier();
        self::assertNull($actual);
    }

    public function testGetUserParameters(): void
    {
        $controller = $this->createController();
        $actual = $controller->getUserParameters();
        $this->assertSameClass(UserParameters::class, $actual);

        // second time for test caching
        $actual = $controller->getUserParameters();
        $this->assertSameClass(UserParameters::class, $actual);
    }

    public function testGetUserParametersWithException(): void
    {
        self::expectException(\LogicException::class);
        $controller = $this->createMockController();
        $controller->getUserParameters();
    }

    public function testPdfDocumentWithException(): void
    {
        self::expectException(NotFoundHttpException::class);
        $controller = $this->createController();
        $report = new class($controller) extends AbstractReport {
            #[\Override]
            public function render(): bool
            {
                return false;
            }
        };
        $controller->renderPdfDocument($report);
    }

    public function testPdfDocumentWithTitle(): void
    {
        $controller = $this->createController();
        $report = new class($controller) extends AbstractReport {
            #[\Override]
            public function render(): bool
            {
                return true;
            }

            #[\Override]
            public function getTitle(): string
            {
                return 'Fake';
            }
        };
        $response = $controller->renderPdfDocument($report);
        self::assertTrue($response->isOk());
    }

    public function testRedirectToHomePage(): void
    {
        $controller = $this->createController();
        $response = $controller->redirectToHomePage();
        self::assertTrue($response->isRedirect());
    }

    public function testRedirectToHomePageWithMessage(): void
    {
        $controller = $this->createController();
        $response = $controller->redirectToHomePage(
            message: TranslatableFlashMessage::instance(
                message: 'log.list.empty',
                parameters: ['key' => 'value'],
            )
        );
        self::assertTrue($response->isRedirect());
    }

    public function testRedirectToHomePageWithRequest(): void
    {
        $request = new Request();
        $controller = $this->createController();
        $response = $controller->redirectToHomePage(request: $request);
        self::assertTrue($response->isRedirect());
    }

    public function testSpreadsheetDocumentWithException(): void
    {
        self::expectException(NotFoundHttpException::class);
        $controller = $this->createController();
        $doc = new class($controller) extends AbstractDocument {
            #[\Override]
            public function render(): bool
            {
                return false;
            }
        };
        $controller->renderSpreadsheetDocument($doc);
    }

    public function testSpreadsheetDocumentWithTitle(): void
    {
        $controller = $this->createController();
        $doc = new class($controller) extends AbstractDocument {
            #[\Override]
            public function render(): bool
            {
                return true;
            }

            #[\Override]
            public function getTitle(): string
            {
                return 'Fake';
            }
        };
        $response = $controller->renderSpreadsheetDocument($doc);
        self::assertTrue($response->isOk());
    }

    public function testWordDocumentWithException(): void
    {
        self::expectException(NotFoundHttpException::class);
        $controller = $this->createController();
        $doc = new class($controller) extends AbstractWordDocument {
            #[\Override]
            public function render(): bool
            {
                return false;
            }
        };
        $controller->renderWordDocument($doc);
    }

    public function testWordDocumentWithTitle(): void
    {
        $controller = $this->createController();
        $doc = new class($controller) extends AbstractWordDocument {
            #[\Override]
            public function render(): bool
            {
                return true;
            }

            #[\Override]
            public function getTitle(): string
            {
                return 'Fake';
            }
        };
        $response = $controller->renderWordDocument($doc);
        self::assertTrue($response->isOk());
    }

    /**
     * @phpstan-param class-string $expected
     */
    private function assertSameClass(string $expected, object $actual): void
    {
        self::assertInstanceOf($expected, $actual);
    }

    private function createController(): FixtureController
    {
        return new FixtureController(self::getContainer());
    }

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
